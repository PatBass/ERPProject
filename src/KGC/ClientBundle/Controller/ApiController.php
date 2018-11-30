<?php

namespace KGC\ClientBundle\Controller;

use KGC\Bundle\SharedBundle\Entity\LandingState;
use KGC\ClientBundle\Entity\SmartFocus;
use KGC\ClientBundle\Entity\Compteur;
use KGC\ClientBundle\Form\ApiLandingDRIUserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\ClientBundle\Form\CardCreateType;
use KGC\ClientBundle\Form\ApiLandingUserType;
use KGC\CommonBundle\Controller\CommonController;
use KGC\RdvBundle\Entity\CarteBancaire;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Entity\TPE;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class ApiController.
 */
class ApiController extends CommonController
{

    private function checkLinkedEntity(LandingUser $prospect, $force = false)
    {
        if (is_null($prospect->getWebsite()) || $force) {
            $website = $this->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            $prospect->setWebsite($website);
        }
        if (is_null($prospect->getSourceConsult()) || $force) {
            $source = $this->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            $prospect->setSourceConsult($source);
        }
        if (is_null($prospect->getCodePromo()) || $force) {
            $codePromo = $this->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            $prospect->setCodePromo($codePromo);
        }
        if (is_null($prospect->getVoyant()) || $force) {
            $voyant = $this->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            $prospect->setVoyant($voyant);
        }
        if (is_null($prospect->getSupport()) || $force) {
            $support = $this->getRepository('KGCRdvBundle:Support')->findOneByLibelle($prospect->getMyastroSupport());
            $prospect->setSupport($support);
        }
        if ((is_null($prospect->getFormurl()) && !is_null($prospect->getWebsite()) && !is_null($prospect->getSourceConsult())) || $force) {
            $find = ['label' => strtolower($prospect->getMyastroUrl())];
            if (!empty($website)) {
                $find['website'] = $website;
            }
            if (!empty($source)) {
                $find['source'] = $source;
            }
            $formurl = $this->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
            $prospect->setFormurl($formurl);
        }
        return $prospect;
    }

    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCSharedBundle:LandingUser';
    }

    /**
     * @param Request $request
     * @param LandingUser $landingUser
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     *
     * @ParamConverter("landingUser", class="KGC\Bundle\SharedBundle\Entity\LandingUser")
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_PHONISTE, ROLE_DRI, ROLE_MANAGER_PHONE")
     */
    public function changeStateAction(Request $request, LandingUser $landingUser)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');
        if ($request->request->has('state', false)) {
            $stateId = $request->request->get('state', false);
            if ($stateId) {
                $state = $em->getRepository('KGCSharedBundle:LandingState')->find($stateId);
                if ($state instanceof LandingState) {
                    $landingUser->setState($state);
                }
                if ($dateState = $request->request->get('dateState', null)) {
                    if ($dateState != '') {
                        $explode = explode(' ', $dateState);
                        if (count($explode)) {
                            $date = $explode[0];
                            if (isset($explode[1])) {
                                $hour = $explode[1];
                            }
                            $explodeDate = explode('/', $date);
                            if (count($explodeDate) == 3) {
                                $hour = $hour ? $hour . ':00' : '00:00:00';
                                $date = new \DateTime($explodeDate[2] . '-' . $explodeDate[1] . '-' . $explodeDate[0]);
                                if ($hour) {
                                    $explodeHour = explode(':', $hour);
                                    if (count($explodeHour)) {
                                        $date->setTime($explodeHour[0] ?: 0, $explodeHour[1] ?: 0);
                                    }
                                }
                                $landingUser->setDateState($date);
                            }
                        }

                    }
                } else {
                    $landingUser->setDateState(null);
                }
                $reload = '#state_' . $stateId;
            } else {
                $landingUser->setState(null);
                $reload = '#dri';
            }

            $em->persist($landingUser);
            $em->flush();
            $this->addFlash('light#pencil-light', $landingUser->getFirstName() . '--DRI modifié.');
            return new Response(
                $serializer->serialize(['success' => true, 'id' => $landingUser->getId(), 'reload' => $reload], 'json'),
                Response::HTTP_OK
            );
        }
        return new Response(
            $serializer->serialize(['success' => false], 'json'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request, $origin = LandingUser::ORIGIN_MYASTRO, $dri = false)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');

        $user = new LandingUser();

        if (count($request->request->all())) {
            $checkSameResult = [
                'questionDate' => new \DateTime(substr($request->request->get('questionDate'), 0, 10)),
                'email' => $request->request->get('email'),
                'origin' => $origin,
            ];
            if ($request->request->get('myastroWebsite')) {
                $checkSameResult['myastroWebsite'] = $request->request->get('myastroWebsite');
            }
            if ($request->request->get('myastroSource')) {
                $checkSameResult['myastroSource'] = $request->request->get('myastroSource');
            }
            if ($request->request->get('myastroUrl')) {
                $checkSameResult['myastroUrl'] = $request->request->get('myastroUrl');
            }

            $findProspect = $em->getRepository("KGCSharedBundle:LandingUser")->findOneBy($checkSameResult);
            if ($findProspect instanceof LandingUser) {
                $user = $findProspect;
            }
        }
        $user->setOrigin($origin);
        $newEntry = false;
        if (!$findProspect instanceof LandingUser) {
            $user->setCreatedAt(new \DateTime);
            $newEntry = true;
        }

        if ($dri) {
            $form = $this->createForm(ApiLandingDRIUserType::class, $user);
        } else {
            $form = $this->createForm(ApiLandingUserType::class, $user);
        }
        $form->handleRequest($request);

        if ($form->isValid()) {
            $client = $this->getRepository()->getProspectClient($user);
            if (!empty($client)) {
                $user->setClient($client);
            }

            $user = $this->checkLinkedEntity($user);

            $em->persist($user);
            $em->flush();

            $this->insertInSmartfocus($user, $newEntry);

            return new Response(
                $serializer->serialize(['success' => true, 'id' => $user->getId()], 'json'),
                Response::HTTP_OK
            );
        } else {
            return new Response(
                $serializer->serialize([
                    'success' => false,
                    'message' => $form->getErrorsAsString(),
                ], 'json'),
                Response::HTTP_OK
            );
        }

        return new Response(
            $serializer->serialize(['success' => false], 'json'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function clicktocallAction(Request $request, $origin = LandingUser::ORIGIN_MYASTRO, $dri = false)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');

        $user = new LandingUser();

        $user->setOrigin($origin);
        $user->setCreatedAt(new \DateTime);
        $newEntry = true;
        foreach($request->query->all() as $key => $value) {
            $request->request->set($key, $value);
        }
        $request->setMethod('POST');
        $form = $this->createForm(ApiLandingUserType::class, $user);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $client = $this->getRepository()->getProspectClient($user);
            if (!empty($client)) {
                $user->setClient($client);
            }

            $user = $this->checkLinkedEntity($user);

            $em->persist($user);
            $em->flush();

            $this->insertInSmartfocus($user, $newEntry);

            return new Response(
                $serializer->serialize(['success' => true, 'id' => $user->getId()], 'json'),
                Response::HTTP_OK
            );
        } else {
            return new Response(
                $serializer->serialize([
                    'success' => false,
                    'message' => $form->getErrorsAsString(),
                ], 'json'),
                Response::HTTP_OK
            );
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createDRIAction(Request $request)
    {
        return $this->createAction($request, LandingUser::ORIGIN_DRI, true);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("landingUser", class="KGC\Bundle\SharedBundle\Entity\LandingUser")
     */
    public function updateDRIAction(Request $request, LandingUser $landingUser)
    {
        return $this->updateAction($request, $landingUser, LandingUser::ORIGIN_DRI, true);
    }
    
    /**
     * @param Request $request
     * @param integer $id_user
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTrackingAction(Request $request, $id_user)
    {
        $tracking_data = false;
        $findProspect = $this->getDoctrine()->getManager()->getRepository("KGCSharedBundle:LandingUser")->findOneById($id_user);
        if ($findProspect instanceof LandingUser) {
            $user = $findProspect;
            $tracking_data = [
                'source' => $user->getMyastroSource(),
                'gclid' => $user->getMyastroGclid()
            ];
        }
        $serializer = $this->get('serializer');
        $tracking_data = $serializer->serialize($tracking_data, 'json');
        
        return new Response($tracking_data, Response::HTTP_OK);
    }
    

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("landingUser", class="KGC\Bundle\SharedBundle\Entity\LandingUser")
     */
    public function updateAction(Request $request, LandingUser $landingUser, $origin = LandingUser::ORIGIN_MYASTRO, $dri = false)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');

        if ($dri) {
            $form = $this->createForm(ApiLandingDRIUserType::class, $landingUser, ['method' => 'PATCH']);
        } else {
            $form = $this->createForm(ApiLandingUserType::class, $landingUser, ['method' => 'PATCH']);
        }

        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $landingUser = $this->checkLinkedEntity($landingUser, true);
            $landingUser->setOrigin($origin);
            $landingUser->setState(null);
            $landingUser->setDateState(null);

            $em->persist($landingUser);
            $em->flush();

            return new Response(
                $serializer->serialize(['success' => true], 'json'),
                Response::HTTP_OK
            );
        } else {
            return new Response(
                $serializer->serialize([
                    'success' => false,
                    'message' => $form->getErrorsAsString(),
                ], 'json'),
                Response::HTTP_OK
            );
        }

        return new Response(
            $serializer->serialize(['success' => false], 'json'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function insertInSmartfocus(LandingUser $landingUser, $newEntry){
        $em = $this->getDoctrine()->getManager();
        $params = array(
            'DATEJOIN'        => $landingUser->getCreatedAt() ? $landingUser->getCreatedAt()->format('d/m/Y H:i:s') : '00/00/0000',
            'DATEMODIF'       => $landingUser->getUpdatedAt() ? $landingUser->getUpdatedAt()->format('Y-m-d H:i:s') : '00/00/0000',
            'SITE'            => $landingUser->getWebsite() ? $landingUser->getWebsite()->getLibelle() : $this->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($landingUser->getMyastroWebsite(), false),
            'SOURCE'          => $landingUser->getSourceConsult() ? $landingUser->getSourceConsult()->getLabel() : $this->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($landingUser->getMyastroSource()),
            'URL'             => $landingUser->getMyastroUrl(),
            'CLIENTURN'       => $landingUser->getQuestionCode(),
            'EMVADMIN2'       => $landingUser->getIsOptinNewsletter()? 'true' : 'false',
            'EMVADMIN3'       => $landingUser->getIsOptinPartner()? 'true' : 'false',
            'DATEOFBIRTH'     => $landingUser->getBirthday() ? $landingUser->getBirthday()->format('d/m/Y') : '00/00/0000',
            'SIGNE'           => $landingUser->getSign(),
            'FIRSTNAME'       => $landingUser->getFirstName(),
            'EMVCELLPHONE'    => intval($landingUser->getPhone()),
            'NUMEROTELEPHONE' => $landingUser->getPhone(),
            'TITLE'           => $landingUser->getGender(),
            'CODE'            => base_convert($landingUser->getMyastroId(), 10, 32),
            'IDASTRO'         => base_convert($landingUser->getMyastroId(), 10, 32),
            'IDKGESTION'         => $landingUser->getId(),
            'FIRSTNAME2'      => $landingUser->getSpouseName(),
            'SIGNE_P2'        => $landingUser->getSpouseSign(),
            'VOYANT'          => $landingUser->getMyastroPsychic(),
            'VOYANT_CODE'     => $landingUser->getMyastroPsychic(),
        );

        if($newEntry){
            $smartFocus = new SmartFocus($em);
            $smartFocus->insert($landingUser->getEmail(), $landingUser->getId(), $params);
        }
//        else{
//            $smartFocus = new SmartFocus($em);
//            $smartFocus->updateMember($landingUser->getEmail(), $landingUser->getId(), $params);
//        }

    }

    /**
     * @param Request $request
     * @param string $hash
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function validateCardHashAction(Request $request, $hash)
    {
        try {
            $rdv = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:RDV')->findOneByNewCardHash($hash);
        } catch (\Exception $e) {
        }

        $response = ['success' => false, 'message' => 'Invalid hash'];

        if (isset($rdv)) {
            if ($rdv->getNewCardHashCreatedAt() >= new \DateTime('-24 hour')) {
                $response = ['success' => true];
            } else {
                $response['message'] = 'Expired hash';
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @param string $hash
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createCardAction(Request $request, $hash)
    {
        try {
            $rdv = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:RDV')->findOneByNewCardHash($hash);
        } catch (\Exception $e) {
        }

        $response = ['success' => false, 'message' => 'Invalid hash'];

        if (isset($rdv)) {
            if (count($rdv->getCartebancaires()) > 0) {
                $response['message'] = 'Credit card already set';
            } else if ($rdv->getNewCardHashCreatedAt() >= new \DateTime('-24 hour')) {
                $form = $this->createForm(new CardCreateType);

                // workaround to avoid
                $request->request->set('firstName', 'empty');
                $request->request->set('lastName', 'empty');

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $response = ['success' => true];

                    $this->get('kgc.rdv.manager')->generateNewCreditCard($rdv, $form->getData());
                } else if ($form->isSubmitted()) {
                    $errors = [];

                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getOrigin()->getName() . ': ' . $error->getMessage();
                    }
                    $response['message'] = implode(', ', $errors);
                } else {
                    $response['message'] = 'Missing card parameters';
                }
            } else {
                $response['message'] = 'Expired hash ' . $rdv->getNewCardHashCreatedAt()->format('c');
            }
        }

        return new JsonResponse($response);
    }
}
