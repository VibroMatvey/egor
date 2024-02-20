<?php

namespace App\Controller;

use App\Controller\Admin\UserCrudController;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class ImportController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack      $requestStack,
        private readonly EntityManagerInterface $em
    )
    {
    }

    #[Route(path: '/import', name: 'import', methods: ['POST'])]
    public function import(Request $request)
    {
        ini_set('max_execution_time', 3600);
        ini_set('default_socket_timeout', 6000);
        ini_set('post_max_size', 9999);
        ini_set('upload_max_filesize', 9999);
        $projectRoot = $this->getParameter('kernel.project_dir');
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        $zip = new ZipArchive();
        /* @var UploadedFile $file */
        $file = $request->files->get('file');
        if (!$file or $file->getMimeType() != 'application/zip') {
            $this->requestStack->getSession()->getFlashBag()->add(
                'danger',
                'Поле "Файл" должно содержать <span class="badge badge-info">*.zip</span> файл'
            );
            return $this->redirect($url);
        }
        $file->move($projectRoot . '/public/zip/', 'test.zip');
        $zip->open($projectRoot .  '/public/zip/' . 'test.zip');
        $zip->extractTo($projectRoot);
        $zip->close();
        $dbName = $this->em->getConnection()->getParams()['dbname'];
        $dbUser = $this->em->getConnection()->getParams()['user'];
        $dbPassword = $this->em->getConnection()->getParams()['password'];
        $outputPath = $projectRoot . '/public/dump.sql';

        $mysqldump = 'mysql';

        if (is_dir('C:\ospanel\modules\database\MySQL-8.0-Win10')) {
            $mysqldump = 'C:\ospanel\modules\database\MySQL-8.0-Win10\bin\mysql';
        }

        $command = sprintf(
            '%s --user=%s --password=%s %s < %s',
            $mysqldump,
            $dbUser,
            $dbPassword,
            $dbName,
            $outputPath
        );

        $process = Process::fromShellCommandline($command);

        $process->run();

        unlink($projectRoot . '/public/zip/test.zip');
        rmdir($projectRoot . '/public/zip');

        if (!$process->isSuccessful()) {
            $process2 = Process::fromShellCommandline('php ' . $projectRoot . '/bin/console d:s:u --force');
            $process2->run();
            $this->addFlash('danger', $process->getErrorOutput());
            return $this->redirect($url);
        }
        $this->addFlash('success', 'Успешный импорт данных');
        return $this->redirect($url);
    }
}