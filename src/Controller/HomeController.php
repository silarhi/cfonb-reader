<?php

namespace App\Controller;

use App\Form\CfonbFileType;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, CacheInterface $cache): Response
    {
        $form = $this->createForm(CfonbFileType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $fileId = uniqid();
            $cache->get($fileId, function(ItemInterface $item) use ($form) {
                $item->expiresAfter(3600);

                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();

                return $file->getContent();
            });

            return $this->redirectToRoute('app_preview', [
                'fileId' => $fileId,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form,
        ]);
    }

    #[Route('/{fileId}', name: 'app_preview')]
    public function test(string $fileId, CacheInterface $cache): Response
    {
        $file = $cache->get($fileId, fn() => null);
        if(null === $file) {
            $this->addFlash('error', 'Le fichier déposé a expiré');

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        return new Response('<html><body>Hello</body></html>');
    }
}
