<?php

namespace Novanta\DynamicEmailSender\Domain\Employee\QueryHandler;

use Novanta\DynamicEmailSender\Domain\Employee\Query\GetEmployeeEmailSenderInfo;

interface GetEmployeeEmailSenderInfoHandlerInterface {    
    public function handle(GetEmployeeEmailSenderInfo $query);
}