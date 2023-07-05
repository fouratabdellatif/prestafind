<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;

use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/home/user')]
class UserAdminController extends AbstractController
{
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
            
        $pieChart = new PieChart();

        $charts = [['User', 'Number per Role']];

        $roleUser = 0;
        $roleAdmin = 0;

        foreach ($users as $u) {
            if($u->getRole() == "user") {
                $roleUser++;
            } else {
                $roleAdmin++;
            }
        }
        array_push($charts, ["ADMIN", $roleAdmin]);
        array_push($charts, ["USER", $roleUser]);
        
        $pieChart->getData()->setArrayToDataTable($charts);

        // dd($pieChart);

        $pieChart->getOptions()->setTitle('User number by Role');
        $pieChart->getOptions()->setHeight(400);
        $pieChart->getOptions()->setWidth(400);
        $pieChart
            ->getOptions()
            ->getTitleTextStyle()
            ->setColor('#07600');
        $pieChart
            ->getOptions()
            ->getTitleTextStyle()
            ->setFontSize(25);
        return $this->render('userAdmin/index.html.twig', [
            'users' => $users,
            'piechart' => $pieChart
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dd($user->getRole());
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->setRoles(['role' => $user->getRole()]);
            $userRepository->save($user, true);

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('userAdmin/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('userAdmin/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/adresses/{id}', name: 'admin_user_show_adresse', methods: ['GET'])]
    public function showAdresses(User $user): Response
    {
        return $this->render('userAdmin/showadresse.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->setRoles(['role' => $user->getRole()]);
            $userRepository->save($user, true);

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('userAdmin/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    
    #[Route('/{id}/block', name: 'admin_user_block', methods: ['GET', 'POST'])]
    public function block(User $user, Request $request): Response
    {
            $user->setIsBlocked(!$user->isBlocked());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            if($user->isBlocked()) {
                $this->addFlash('success', 'User blocked.');
            } else {
                $this->addFlash('success', 'User unblocked.');
            }

            return $this->redirectToRoute('admin_user_index');

        return $this->render('userAdmin/block.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/editprofile', name: 'admin_user_edit_profile', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, User $user, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        // $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            
            $userRepository->save($user, true);
            if($user->getRole() == "admin") {
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('app_home_front', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('userAdmin/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{id}/deleteprofile', name: 'admin_user_delete_profile', methods: ['POST'])]
    public function deleteAccount(Request $request, User $user, UserRepository $userRepository, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authChecker): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        $response = new Response();
        $response->setContent('Your account has been deleted successfully. You have been logged out.');
        
        if ($authChecker->isGranted('ROLE_USER')) {
            $tokenStorage->getToken()->setAuthenticated(false);
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route ('/printuser/{id}', name: 'print_user')]
    public function exportUserPDF($id, UserRepository $repo)
    {
        // On définit les options du PDF
        $pdfOptions = new Options();
        // Police par défaut
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->setIsRemoteEnabled(true);

        // On instancie Dompdf
        $dompdf = new Dompdf();
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $dompdf->setHttpContext($context);
        $user = $repo->find($id);
        // dd($users);

        // On génère le html
        $html = $this->renderView(
            'userAdmin/print.html.twig',
            [
                'user' => $user
            ]
        );

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // On génère un nom de fichier
        $fichier = 'user'. $user->getEmail() . date('c') .'.pdf';

        // On envoie le PDF au navigateur
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        return new Response();
    }

    #[Route ('/printallusers/{id}', name: 'print_users')]
    public function exportAllUsersPDF(UserRepository $repo)
    {
        // On définit les options du PDF
        $pdfOptions = new Options();
        // Police par défaut
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->setIsRemoteEnabled(true);

        // On instancie Dompdf
        $dompdf = new Dompdf($pdfOptions);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $dompdf->setHttpContext($context);
        $users = $repo->findAll();
        // dd($users);

        // On génère le html
        $html = $this->renderView(
            'userAdmin/printall.html.twig',
            [
                'users' => $users
            ]
        );

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // On génère un nom de fichier
        $fichier = 'users'. date('c') .'.pdf';

        // On envoie le PDF au navigateur
        $dompdf->stream($fichier, [
            'Attachment' => true
        ]);

        return new Response();
    }
}
