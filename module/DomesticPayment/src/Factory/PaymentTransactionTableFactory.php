<?php
declare(strict_types=1);

namespace DomesticPayment\Factory;

use Psr\Container\ContainerInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;
use DomesticPayment\Model\PaymentTransaction;
use DomesticPayment\Model\PaymentTransactionTable;

class PaymentTransactionTableFactory
{
    public function __invoke(ContainerInterface $container): PaymentTransactionTable
    {
        // Get the database adapter from the service manager
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        
        // Create a ResultSet with the PaymentTransaction entity mapping
        $resultSetPrototype = new ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new PaymentTransaction());
        
        // Create the TableGateway for the payment_transaction table
        $tableGateway = new TableGateway('payment_transaction', $adapter, null, $resultSetPrototype);
        
        // Return the PaymentTransactionTable with the TableGateway
        return new PaymentTransactionTable($tableGateway);
    }
}
