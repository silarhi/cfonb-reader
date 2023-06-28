<?php

namespace App\Controller;

use App\Form\CfonbFileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(CfonbFileType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('app_preview', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form,
        ]);
    }

    #[Route('/preview', name: 'app_preview')]
    public function test(Request $request): Response
    {
        return new Response('<html><body>Hello</body></html>');
    }
}
