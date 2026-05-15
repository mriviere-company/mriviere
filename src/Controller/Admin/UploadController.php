<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Single-purpose upload endpoint for the admin's profile photo (and any future
 * file fields the back-office grows). Validates MIME + size, stores under
 * `public/uploads/{kind}/`, returns the public URL.
 *
 * Why not generic Flysystem/S3? Hostinger mutualisé doesn't ship cloud storage
 * by default and the volume is tiny (one image). Local FS is plenty.
 */
#[Route('/api/admin/upload', name: 'api_admin_upload_')]
#[IsGranted('ROLE_ADMIN')]
final class UploadController extends AbstractController
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_KINDS = ['profile'];

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $publicBaseUrl,
    ) {
    }

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $kind = (string) $request->request->get('kind', 'profile');
        if (!in_array($kind, self::ALLOWED_KINDS, true)) {
            return $this->json(['error' => 'Type d\'upload non autorisé.'], 400);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(['error' => 'Aucun fichier reçu (champ `file`).'], 400);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return $this->json(['error' => sprintf('Fichier trop gros (max %d MB).', self::MAX_BYTES / 1024 / 1024)], 413);
        }

        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return $this->json(['error' => 'Format non autorisé. Acceptés : JPEG, PNG, WebP.'], 415);
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe = $this->slugger->slug($original)->lower();
        $ext = $file->guessExtension() ?? 'bin';
        $filename = sprintf('%s-%s.%s', $safe, bin2hex(random_bytes(4)), $ext);

        $targetDir = sprintf('%s/public/uploads/%s', $this->getParameter('kernel.project_dir'), $kind);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return $this->json(['error' => 'Impossible de créer le dossier de destination.'], 500);
        }

        try {
            $file->move($targetDir, $filename);
        } catch (FileException $e) {
            return $this->json(['error' => 'Échec de l\'upload : ' . $e->getMessage()], 500);
        }

        $publicPath = sprintf('/uploads/%s/%s', $kind, $filename);

        return $this->json([
            'url' => $publicPath,
            'absoluteUrl' => rtrim($this->publicBaseUrl, '/') . $publicPath,
            'mime' => $mime,
            'size' => $file->getSize() ?: filesize($targetDir . '/' . $filename) ?: null,
        ], 201);
    }
}
