<?php

namespace Novanta\DynamicEmailSender\Domain\Employee\Query;

class GetEmployeeEmailSenderInfo {

    private $id_employee;

    public function __construct($id_employee) 
    {
        $this->id_employee = $id_employee;
    }

    public function getIdEmployee()
    {
        return $this->id_employee;
    }
}