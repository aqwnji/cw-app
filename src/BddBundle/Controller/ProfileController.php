<?php

namespace BddBundle\Controller;

use BddBundle\Entity\User;
use BddBundle\Form\Type\ProfileType;
use BddBundle\Service\BannerMaker;
use BddBundle\Service\StatService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends Controller
{
    /**
     * @param Request $request
     * @param BannerMaker $bannerMaker
     * @param StatService $statService
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/me", name="me")
     * @Method({"GET", "POST"})
     * @Security("is_granted('ROLE_USER')")
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function meAction(Request $request, BannerMaker $bannerMaker, StatService $statService)
    {
        $user = $this->getUser();

        // @todo async
        $bannerMaker->makeBanner($user);

        /** @var Form $form */
        $form = $this->createForm(
            ProfileType::class,
            $user,
            [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastName(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('me');
        }

        return $this->render(
            'BddBundle:Profile:me.html.twig',
            [
                'user' => $this->getUser(),
                'form' => $form->createView(),
                'stats' => $statService->getUserStats($user)
            ]
        );
    }

    /**
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/me/ratings/{page}", name="me_ratings", requirements={"page" = "\d+"})
     * @Method({"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function ratingsAction($page = 1)
    {
        /** @var User $user */
        $user = $this->getUser();
        $dql = 'SELECT r FROM BddBundle:RiddenCoaster r 
                JOIN r.user u 
                JOIN r.coaster c 
                JOIN c.builtCoaster bc 
                JOIN bc.manufacturer m
                WHERE u.id = ?1';
        $query = $this->get('doctrine.orm.entity_manager')->createQuery($dql);
        $query->setParameter(1, $user->getId());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $page,
            30,
            [
                'wrap-queries' => true,
                'defaultSortFieldName' => 'r.updatedAt',
                'defaultSortDirection' => 'desc',
            ]
        );

        return $this->render(
            'BddBundle:Profile:ratings.html.twig',
            [
                'ratings' => $pagination,
            ]
        );
    }

    /**
     * @Route("/me/reports", name="me_reports")
     * @Method({"GET"})
     * @Security("is_granted('ROLE_USER')")
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function myReportsAction(EntityManagerInterface $em)
    {
        $reports = $em->getRepository('BddBundle:Report')->findBy(
            ['user' => $this->getUser()],
            ['updateDate' => 'desc']
        );

        return $this->render(
            'BddBundle:Profile:reports.html.twig',
            [
                'reports' => $reports,
            ]
        );
    }

    /**
     * Display a user's profile page
     *
     * @param User $user
     * @param StatService $statService
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @Route("/users/{id}/profile", name="user_profile")
     * @Method({"GET"})
     */
    public function publicProfileAction(User $user, StatService $statService)
    {
        return $this->render(
            'BddBundle:Profile:public.html.twig',
            [
                'user' => $user,
                'stats' => $statService->getUserStats($user)
            ]
        );
    }
}
