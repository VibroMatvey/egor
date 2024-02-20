<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class ExportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    )
    {
    }

    #[Route(path: '/export', name: 'export', methods: ['GET'])]
    public function export(Request $request)
    {
        ini_set('max_execution_time', 3600);
        ini_set('default_socket_timeout', 6000);
        ini_set('post_max_size', 9999);
        ini_set('upload_max_filesize', 9999);
        $projectRoot = $this->getParameter('kernel.project_dir');
        if (is_file($projectRoot . '/public/dump.zip')) {
            unlink($projectRoot . '/public/dump.zip');
        }
        if (is_file($projectRoot . '/public/dump.sql')) {
            unlink($projectRoot . '/public/dump.sql');
        }
        $dbName = $this->em->getConnection()->getParams()['dbname'];
        $dbUser = $this->em->getConnection()->getParams()['user'];
        $dbPassword = $this->em->getConnection()->getParams()['password'];
        $outputPath = 'dump.sql';

        $mysqldump = 'mysqldump';

        if (is_dir('C:\ospanel\modules\database\MySQL-8.0-Win10')) {
            $mysqldump = 'C:\ospanel\modules\database\MySQL-8.0-Win10\bin\mysqldump.exe';
        }

        $command = sprintf(
            '%s --user=%s --password=%s %s > %s',
            $mysqldump,
            $dbUser,
            $dbPassword,
            $dbName,
            $outputPath
        );

        $process = Process::fromShellCommandline($command);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $zip = new ZipArchive();

        $zip->open($projectRoot . '/public/dump.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folderToZip = '../public';

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderToZip));

        foreach ($files as $name => $file) {
            if (
                $name == '../public\index.php' or
                $name == '../public\.htaccess' or
                key_exists(1, explode('\\', $file->getPath())) ? explode('\\', $file->getPath())[1] == 'bundles' : null or
                    key_exists(1, explode('\\', $file->getPath())) ? explode('\\', $file->getPath())[1] == 'vich' : null
            ) {
                continue;
            }
            if (!$file->isDir()) {
                $zip->addFile(str_replace('\\', '/', $file->getPathname()));
            }
        }
        $zip->close();

        $zip->addFile($projectRoot . '/public/dump.sql');

        $response = new BinaryFileResponse($projectRoot . '/public/dump.zip');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'НАЗВАНИЕ ПРОЕКТА.zip'
        );
        return $response;
    }
}