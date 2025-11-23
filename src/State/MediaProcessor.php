<?php
// src/State/MediaProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Media;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentWorkspace $ctx,
        private Security         $security,
        private string           $uploadDir, // ex: '%kernel.project_dir%/public/uploads'
        private SluggerInterface $slugger)
    {
    }

    public function process($data, Operation $operation, array $uriUriVariables = [], array $context = [])
    {
        if (!$data instanceof Media) return $data;

        $ws = $this->ctx->get();
        $data->setWorkspace($ws);

        if ($this->security->getUser()) {
            $data->setAuthor($this->security->getUser());


            $file = $data->getFile();
            if ($file instanceof UploadedFile) {
                $workspaceSlug = $ws->getSlug();
                $targetDir = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $workspaceSlug;

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = $this->slugger->slug($original)->lower();
                $filename = sprintf('%s-%s.%s', $safeName, uniqid('', true), $file->guessExtension() ?: $file->getClientOriginalExtension());

                $file->move($targetDir, $filename);

                // stocker le chemin relatif public (utilisable dans les URLs)
                $data->setPath('/uploads/' . $workspaceSlug . '/' . $filename);

                // supprimer la référence UploadedFile (n'est pas persisté)
                $data->setFile(null);
            }
        }
        return $data;
    }
}
