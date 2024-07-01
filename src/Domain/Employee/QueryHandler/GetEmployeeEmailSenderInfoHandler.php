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

namespace Novanta\DynamicEmailSender\Domain\Employee\QueryHandler;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Novanta\DynamicEmailSender\Domain\Employee\Query\GetEmployeeEmailSenderInfo;

class GetEmployeeEmailSenderInfoHandler implements GetEmployeeEmailSenderInfoHandlerInterface
{
    private $connection;
    private $dbPrefix;

    public function __construct(
        Connection $connection,
        $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    public function handle(GetEmployeeEmailSenderInfo $query)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('
                e.id_employee,
                e.firstname,
                e.lastname,
                e.email,
                ees.enabled as `enabled_as_email_sender`,
                ees.name as `email_sender_name`
            ')
            ->from($this->dbPrefix . 'employee', 'e')
            ->innerJoin('e', $this->dbPrefix . 'employee_email_sender', 'ees', 'e.id_employee = ees.id_employee')
            ->where('e.id_employee = :idEmployee');

        $queryBuilder->setParameter('idEmployee', $query->getIdEmployee());

        return $queryBuilder->execute()->fetch();
    }
}
