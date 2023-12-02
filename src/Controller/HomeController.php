<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Controller;

use App\Cfonb\CfonbManager;
use App\Exception\DataNotFoundException;
use App\Exception\DataNotReadableException;
use App\Exporter\Cfonb120CsvExporter;
use App\Exporter\Cfonb240CsvExporter;
use App\Form\CfonbFileType;
use App\Storage\DataStorageHandler;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, DataStorageHandler $dataStorageHandler): Response
    {
        $form = $this->createForm(CfonbFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $data = [
                'type' => $form->get('type')->getData(),
                'content' => $file->getContent(),
                'strict' => $form->get('strict')->getData(),
            ];

            $id = $dataStorageHandler->store($data);

            return $this->redirectToRoute('app_preview', [
                'id' => $id,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('home/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_preview')]
    public function preview(
        string $id,
        DataStorageHandler $dataStorageHandler,
        CfonbManager $cfonbManager,
    ): Response {
        try {
            $data = $dataStorageHandler->get($id);
        } catch (DataNotFoundException) {
            $this->addFlash('error', "Le fichier déposé n'est plus disponible.");

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        $content = $data['content'];
        $type = $data['type'] ?? $cfonbManager->guessTypeFromContent($content);
        $strict = $data['strict'] ?? false;

        try {
            $data = $cfonbManager->getData($content, $type, $strict);
        } catch (DataNotReadableException) {
            $this->addFlash('error', 'Le fichier déposé ne ressemble pas au format CFONB.');

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('preview/index.html.twig', [
            'data' => $data,
            'type' => $type,
            'id' => $id,
        ]);
    }

    #[Route('/{id}/export/csv', name: 'app_export_csv')]
    public function exportCSV(
        string $id,
        DataStorageHandler $dataStorageHandler,
        CfonbManager $cfonbManager,
        Cfonb120CsvExporter $cfonb120CsvExporter,
        Cfonb240CsvExporter $cfonb240CsvExporter
    ): Response {
        try {
            $data = $dataStorageHandler->get($id);
        } catch (DataNotFoundException) {
            $this->addFlash('error', "Le fichier déposé n'est plus disponible.");

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        $content = $data['content'];
        $type = $data['type'] ?? $cfonbManager->guessTypeFromContent($content);
        $strict = $data['strict'] ?? false;

        try {
            $data = $cfonbManager->getData($content, $type, $strict);
        } catch (DataNotReadableException) {
            $this->addFlash('error', 'Le fichier déposé ne ressemble pas au format CFONB.');

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        $tempFile = CfonbManager::TYPE_120 === $type
            ? $cfonb120CsvExporter->export($data, $cfonbManager->getAllCfonb120Metadata())
            : $cfonb240CsvExporter->export($data, $cfonbManager->getAllCfonb240Metadata());

        $fileName = sprintf('%s_%s.csv', (new DateTimeImmutable())->format('YmdHis'), $type);

        $response = new BinaryFileResponse($tempFile);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
