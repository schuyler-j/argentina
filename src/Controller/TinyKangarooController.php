<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Form\TinyKangarooType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TinyKangarooController extends AbstractController
{
    #[Route('/tiny/kangaroo', name: 'app_tiny_kangaroo')]
    public function index(): Response
    {
        return $this->render('tiny_kangaroo/index.html.twig', [
            'controller_name' => 'TinyKangarooController',
        ]);
    }

    #[Route('/lucky', name: 'lucky')]
    public function submitForm(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepo, ValidatorInterface $validator, Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $form = $this->createForm(TinyKangarooType::class);
        $form->handleRequest($request);

        // test if user is logged in

        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($urlGenerator->generate('index'));
        }

        $userEmail = $request->getSession()->get(Security::LAST_USERNAME);
        $queryBuilder = $entityManager->createQueryBuilder();

        $userId = $queryBuilder
            ->select('u.id')
            ->from('App\Entity\User', 'u')
            ->where('u.email = :email')
            ->setParameter('email', $userEmail)
            ->getQuery()
            ->getSingleScalarResult();

        $user = $userRepo->find($userId);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();
            $website = $formData->getWebsite();
            $user->setWebsite($website);
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorString = (string) $errors;
                return new Response($errorString);
            }

            $entityManager->flush();
            return $this->redirectToRoute('lucky', ['website' => $website]);
        }

        $hello = $user->getWebsite();

        return $this->render('number.html.twig', ['form' => $form->createView(), 'hello' => $hello]);
    }
}
