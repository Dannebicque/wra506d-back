<?php
// src/State/MediaProcessor.php
namespace App\State;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

final class MediaProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CurrentWorkspace $ctx,
        private EntityManagerInterface $em,
        private IriConverterInterface $iriConverter,
        private Security         $security,
        private string           $uploadDir, // ex: '%kernel.project_dir%/public/uploads'
        )
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $ws = $this->ctx->get();
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('No current request');
        }

        $file = $request->files->get('file');
        $originalName = $request->request->get('originalName') ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : null);
        $publicationIri = $request->request->get('publication') ?? $request->request->get('channel');

        $media = new Media();
        $media->setWorkspace($ws);
        $media->setOriginalName($originalName ?? '');
        $media->setCreatedAt(new \DateTimeImmutable());

        if ($file instanceof UploadedFile) {
            $workspaceSlug = $ws->getSlug();
            $targetDir = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $workspaceSlug;

            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $targetDir));
                }
            }
//
            $filename = uniqid('', true) . '.' . ($file->guessExtension() ?: 'bin');
            $file->move($targetDir, $filename);

            $media->setPath($filename);
            $media->setMimeType($file->getClientMimeType() ?? '');
            $media->setSize((int) $file->getSize());
        }

        if ($publicationIri) {
            try {
                $publication = $this->iriConverter->getResourceFromIri($publicationIri);
                $media->setPublication($publication);
            } catch (\Throwable $e) {
                throw new \RuntimeException('Publication IRI invalide');
            }
        }

        $this->em->persist($media);
        $this->em->flush();

        return $media;

//        if (!$data instanceof Media) return $data;
//
//        $ws = $this->ctx->get();
//        $data->setWorkspace($ws);
//
//        if ($this->security->getUser()) {
//            $data->setAuthor($this->security->getUser());
//
//
//            $file = $data->getFile();
//            if ($file instanceof UploadedFile) {
//                $workspaceSlug = $ws->getSlug();
//                $targetDir = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $workspaceSlug;
//
//                if (!is_dir($targetDir)) {
//                    mkdir($targetDir, 0755, true);
//                }
//
//                $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//                $safeName = $this->slugger->slug($original)->lower();
//                $filename = sprintf('%s-%s.%s', $safeName, uniqid('', true), $file->guessExtension() ?: $file->getClientOriginalExtension());
//
//                $file->move($targetDir, $filename);
//
//                // stocker le chemin relatif public (utilisable dans les URLs)
//                $data->setPath('/uploads/' . $workspaceSlug . '/' . $filename);
//
//                // supprimer la référence UploadedFile (n'est pas persisté)
//                $data->setFile(null);
//            }
//        }
//        return $data;
    }
}
