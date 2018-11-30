<?php

namespace KGC\RdvBundle\GclidExporter;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @DI\Service("kgc.rdv.exporter.gclid.excel")
 */
class ExcelExporter extends Exporter implements ExporterInterface
{
    protected $excelService;

    /**
     * @return string
     */
    protected function getExtension()
    {
        return 'xlsx';
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param $phpExcel
     *
     * @DI\InjectParams({
     *     "entityManager" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManagerInterface $entityManager, $phpExcel)
    {
        parent::__construct($entityManager);
        $this->excelService = $phpExcel;
    }

    /**
     * @param array $config
     */
    public function export(array $config = [])
    {
        $phpExcelObject = $this->excelService->createPHPExcelObject();

        $phpExcelObject->getProperties()->setCreator('KGESTION')
            ->setLastModifiedBy('KGESTION')
            ->setTitle('GCLID Export')
            ->setSubject('GCLID Export')
            ->setDescription('GCLID Export')
        ;
        $phpExcelObject->setActiveSheetIndex(0);

        $index = 1;
        $phpExcelObject->getActiveSheet()->fromArray($this->getParametersLine(), null, 'A'.$index++);
        $phpExcelObject->getActiveSheet()->fromArray($this->getHeadersLine(), null, 'A'.$index++);

        $items = $this->getGclidItemsOrQb($config['begin'], $config['end'], true)->getQuery()->iterate();

        while (false !== ($row = $items->next())) {
            $data = $this->getBusinessLine($row[0]);
            $phpExcelObject->getActiveSheet()->fromArray($data, null, 'A'.$index++);
            $this->entityManager->detach($row[0]);
        }

        $phpExcelObject->getActiveSheet()->setTitle('GCLID Export');
        $phpExcelObject->setActiveSheetIndex(0);

        $writer = $this->excelService->createWriter($phpExcelObject, 'Excel2007');
        $response = $this->excelService->createStreamedResponse($writer);

        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename = $this->generateFileName()
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'max-age=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        $this->response = $response;
    }
}
