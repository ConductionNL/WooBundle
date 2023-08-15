<?php

namespace CommonGateway\PDDBundle\Service;

use App\Service\SynchronizationService;
use CommonGateway\CoreBundle\Service\CallService;
use CommonGateway\CoreBundle\Service\GatewayResourceService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncCasesService
{

    private GatewayResourceService $resourceService;

    private CallService $callService;

    private SynchronizationService $syncService;

    private SymfonyStyle $style;

    private array $data;

    private array $configuration;


    public function __construct(
        GatewayResourceService $resourceService,
        CallService $callService,
        SynchronizationService $syncService
    ) {
        $this->resourceService = $resourceService;
        $this->callService     = $callService;
        $this->syncService     = $syncService;

    }//end __construct()

    /**
     * Set symfony style in order to output to the console.
     *
     * @param SymfonyStyle $style
     *
     * @return self
     *
     * @todo change to monolog
     */
    public function setStyle(SymfonyStyle $style): self
    {
        $this->style = $style;

        return $this;

    }//end setStyle()

    /**
     * Handles the synchronization of xxllnc cases.
     *
     * @param array $data
     * @param array $configuration
     *
     * @throws CacheException|InvalidArgumentException
     *
     * @return array
     */
    public function syncCasesHandler(array $data, array $configuration): array
    {
        $this->data          = $data;
        $this->configuration = $configuration;

        isset($this->style) === true && $this->style->success('SyncCasesService triggered');

        $sourceRef = 'https://commongateway.woo.nl/source/noordwijk.zaaksysteem.source.json';
        $source  = $this->resourceService->getSource($sourceRef, 'common-gateway/pdd-bundle');
        if ($source === null) {
            isset($this->style) === true && $this->style->error("$sourceRef not found.");
            return [];
        }
        $schemaRef = 'https://commongateway.nl/pdd.openWOO.schema.json';
        $schema  = $this->resourceService->getSchema($schemaRef, 'common-gateway/pdd-bundle');
        if ($schema === null) {
            isset($this->style) === true && $this->style->error("$schemaRef not found.");
            return [];
        }
        $mappingRef = 'https://commongateway.nl/mapping/pdd.xxllncCaseToWoo.schema.json';
        $mapping = $this->resourceService->getMapping($mappingRef, 'common-gateway/pdd-bundle');
        if ($mapping === null) {
            isset($this->style) === true && $this->style->error("$mappingRef not found.");
            return [];
        }

        $sourceConfig = $source->getConfiguration();

        isset($this->style) === true && $this->style->info("Fetching cases from {$source->getLocation()}");

        $response = $this->callService->call($source, '', 'GET', $sourceConfig);
        $decodedResponse = $this->callService->decodeResponse($source, $response);

        $responseItems = [];
        foreach ($decodedResponse['result'] as $result) {
            $synchronization = $this->syncService->findSyncBySource($source, $schema, $result['id']);
            $synchronization->setMapping($mapping);
            $synchronization = $this->syncService->synchronize($synchronization, $result);

            $responseItems[] = $synchronization->getObject()->toArray();
        }

        $this->data['response'] = new Response(json_encode($responseItems), 200);

        dump($responseItems);
        isset($this->style) === true && $this->style->success("Synchronized cases to woo objects.");

        return $this->data;

    }//end syncCasesHandler()


}//end class
