<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use Doctrine\DBAL\Query\QueryBuilder;
use Novanta\DynamicEmailSender\Domain\Employee\Query\GetEmployeeEmailSenderInfo;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

$autoloadPath = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dynamicemailsender extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'dynamicemailsender';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Novanta';
        $this->need_instance = 1;

        parent::__construct();

        $this->displayName = $this->trans('Dynamic Email Sender', [], 'Modules.Dynamicemailsender.Admin');
        $this->description = $this->trans('Can handle email sender based on employee that trigger email', [], 'Modules.Dynamicemailsender.Admin');

        $this->confirmUninstall = $this->trans('Do you want to uninstall module?', [], 'Modules.Dynamicemailsender.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $configuration = SymfonyContainer::getInstance()->get('prestashop.adapter.legacy.configuration');

        return parent::install()
            && $this->registerHook('actionEmailSendBefore')
            && $this->registerHook('actionEmployeeFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateEmployeeFormHandler')
            && $this->registerHook('actionAfterCreateEmployeeFormHandler')
            && $this->installTables();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTables();
    }

    private function installTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'employee_email_sender` (
            `id_employee` INT(10) UNSIGNED NOT NULL,
            `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\',
            `name` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id_employee`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallTables()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'employee_email_sender`';

        return Db::getInstance()->execute($sql);
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    /**
     * Hook che modifica il mittente delle email
     * sulla base del' employee che effettua l'azione
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionEmailSendBefore($params)
    {
        global $cookie;
        $container = SymfonyContainer::getInstance();

        if ($cookie && $cookie->id_employee) {
            $request = new GetEmployeeEmailSenderInfo($cookie->id_employee);
            $employee = $container->get('prestashop.core.query_bus')->handle($request);

            if ($employee && $employee['enabled_as_email_sender']) {
                $params['from'] = $employee['email'];
                $params['fromName'] =
                    $employee['email_sender_name'] ?
                    $employee['email_sender_name'] :
                        ($employee['name'] + ' ' + $employee['lastname'] + ' | ' + $container->get('prestashop.adapter.legacy.configuration')->get('PS_SHOP_NAME'));
            }
        }
    }

    /**
     * Funzione per modificare la form del' Employee
     *
     * @param array $hookParams
     *
     * @return void
     */
    public function hookActionEmployeeFormBuilderModifier(&$hookParams)
    {
        $formBuilder = $hookParams['form_builder'];
        $formBuilder
            ->add('enabled_as_email_sender', SwitchType::class, [
                'label' => $this->trans('Enable as Email Sender', [], 'Modules.Dynamicemailsender.Admin'),
                'empty_data' => 1,
            ]
            )
            ->add('email_sender_name', TextType::class, [
                'label' => $this->trans('Email From Name', [], 'Modules.Dynamicemailsender.Admin'),
                'attr' => [
                    'placeholder' => 'ex. John Doe | Prestashop',
                ],
                'required' => false,
            ]
            );

        // ToDo chiamata CQRS per recuperare le informazioni del dipendente
        // così che poi posso riempire la form di modiifca, se creazioni non faccio nulla
        if (array_key_exists('id', $hookParams) && $hookParams['id']) {
            $request = new GetEmployeeEmailSenderInfo($hookParams['id']);
            $employee = SymfonyContainer::getInstance()->get('prestashop.core.query_bus')->handle($request);

            if ($employee) {
                $hookParams['data']['enabled_as_email_sender'] = $employee['enabled_as_email_sender'];
                $hookParams['data']['email_sender_name'] = $employee['email_sender_name'];
                $formBuilder->setData($hookParams['data']);
            }
        }
    }

    /**
     * Funzione che salva le informazioni extra dell'Emplyee in fase di modifica
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionAfterUpdateEmployeeFormHandler($params)
    {
        // Salva le informazioni aggiuntive dell'employee
        if (array_key_exists('id', $params) && $params['id']) {
            $container = SymfonyContainer::getInstance();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $container->get('database_connection')->createQueryBuilder();
            $queryBuilder
                ->select('count(id_employee)')
                ->from(_DB_PREFIX_ . 'employee_email_sender', 'ees')
                ->where('ees.id_employee = :idEmployee');

            $queryBuilder->setParameter('idEmployee', $params['id']);
            $exists_email_sender_info = (bool) $queryBuilder->execute()->fetchColumn();

            if (!$exists_email_sender_info) {
                $queryBuilder
                    ->insert(_DB_PREFIX_ . 'employee_email_sender')
                    ->values([
                        'id_employee' => $params['id'],
                        'enabled' => ':enabled',
                        'name' => ':name',
                    ])
                    ->setParameters([
                        ':enabled' => $params['form_data']['enabled_as_email_sender'],
                        ':name' => $params['form_data']['email_sender_name'],
                    ]);

                $queryBuilder->execute();
            } else {
                $queryBuilder
                    ->update(_DB_PREFIX_ . 'employee_email_sender', 'ees')
                    ->set('ees.enabled', ':enabled')
                    ->set('ees.name', ':name')
                    ->where('ees.id_employee = :idEmployee')
                    ->setParameter(':idEmployee', $params['id'])
                    ->setParameter(':enabled', $params['form_data']['enabled_as_email_sender'])
                    ->setParameter(':name', $params['form_data']['email_sender_name']);

                $queryBuilder->execute();
            }
        }
    }

    /**
     * Funzione che salva le informazioni extra dell'Employee in fase di creazione
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionAfterCreateEmployeeFormHandler($params)
    {
        if (array_key_exists('id', $params) && $params['id']) {
            $container = SymfonyContainer::getInstance();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $container->get('database_connection')->createQueryBuilder();
            $queryBuilder
                ->insert(_DB_PREFIX_ . 'employee_email_sender')
                ->values([
                    'id_employee' => $params['id'],
                    'enabled' => ':enabled',
                    'name' => ':name',
                ])
                ->setParameters([
                    ':enabled' => $params['form_data']['enabled_as_email_sender'],
                    ':name' => $params['form_data']['email_sender_name'],
                ]);

            $queryBuilder->execute();
        }
    }
}
