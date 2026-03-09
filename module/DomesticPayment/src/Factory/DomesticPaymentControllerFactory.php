<?php
declare(strict_types=1);

namespace DomesticPayment\Factory;

use DomesticPayment\Controller\DomesticPaymentController;
use DomesticPayment\Service\RmaPaymentService;
use DomesticPayment\Model\PaymentTransactionTable;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DomesticPaymentControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $rmaPaymentService = $container->get(RmaPaymentService::class);
        $paymentTransactionTable = $container->get(PaymentTransactionTable::class);

        return new DomesticPaymentController($rmaPaymentService, $paymentTransactionTable);
    }
}
