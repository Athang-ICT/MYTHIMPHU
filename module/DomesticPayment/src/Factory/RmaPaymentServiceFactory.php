<?php
declare(strict_types=1);

namespace DomesticPayment\Factory;

use DomesticPayment\Service\RmaPaymentService;
use Interop\Container\ContainerInterface;
use Laminas\Http\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\LoggerInterface;

class RmaPaymentServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        
        // Get RMA API configuration (from rma_api config key)
        $rmaConfig = $config['rma_api'] ?? [];
        
        $apiUrl = $rmaConfig['base_url'] ?? 'https://apipg.athang.com:8080/api/v1';
        $jwtToken = $rmaConfig['jwt_secret'] ?? '';

        $httpClient = new Client();
        
        $logger = null;
        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
        }

        return new RmaPaymentService($apiUrl, $jwtToken, $httpClient, $logger);
    }
}
