<?php

namespace KGC\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CommonController.
 */
abstract class CommonController extends Controller
{
    /**
     * @return mixed
     */
    protected function getRefererParams(Request $request)
    {
        //        $referer = $request->headers->get('referer');
//        $baseUrl = $request->getBaseUrl();
//        $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));
//
//        $match = $this->get('router')->getMatcher()->match($lastPath);
//
//        return !empty($match) ? $match : array();

        return [];
    }

    /**
     * @param null $entityName
     *
     * @return mixed
     */
    protected function getRepository($entityName = null)
    {
        $entityName = $entityName ?: $this->getEntityRepository();

        return $this->getDoctrine()->getManager()->getRepository($entityName);
    }

    /**
     * @return mixed
     */
    protected function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    protected function findById($id)
    {
        $object = $this->getRepository()->findOneById($id);
        if (null === $object) {
            throw new NotFoundHttpException(
                sprintf('Object with id: %d not found', $id)
            );
        }

        return $object;
    }

    /**
     * @param array $data
     *
     * @return Response
     */
    protected function jsonResponse(array $data = [])
    {
        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @param string $type
     * @param string $msg
     *
     * @return mixed
     */
    protected function addFlash($type, $msg)
    {
        return $this->get('session')->getFlashBag()->add($type, $msg);
    }

    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    abstract protected function getEntityRepository();
}
