<?php 

namespace Novanta\DynamicEmailSender\Domain\Employee\QueryHandler;

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