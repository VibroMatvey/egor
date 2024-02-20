<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->renderContentMaximized()
            ->setTitle('<b>Админ-панель</b>');
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addHtmlContentToBody('
                        <div id="import" class="modal fade" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body p-0">
                                         <form enctype="multipart/form-data" action="/import" method="post" >
                                             <div class="filter-field border-bottom py-4 px-3 d-flex flex-column gap-4" data-filter-property="buyer"> 
                                              <input class="form-control" type="file" id="file" name="file" accept="zip,application/octet-stream,application/zip,application/x-zip,application/x-zip-compressed">
                                              <input class="btn btn-success" type="submit" value="Отправить файл" />
                                             </div>
                                             <div class="p-2">
                                                <p class="fw-bold"><span class="badge badge-danger p-1">Внимание!</span> При импорте, предыдущие изменения не сохранятся.</p>
                                                <span class="badge badge-success p-1">Рекомендуем: </span></br> 
                                                1. Выполнить экспорт, перед импортом, для сохранения предыдущих значений. </br> 
                                                2. Удостовериться, что в архиве находятся актуальные данные для текущего приложения. </br> 
                                                <span class="badge badge-warning p-1">Важно!</span></br>  
                                                Если конечный архив превышает 250мб, необходимо изменить конфигурацию php (php.ini):</br> 
                                                1. max_execution_time = 360</br> 
                                                2. post_max_size = 9999M</br> 
                                                3. upload_max_filesize = 9999M</br> </br> 
                                                Конфигурацию nginx (nginx.conf):</br> 
                                                1. client_max_body_size 9999M;
                                             </div>
                                         </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Настройки');
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-user-cog', User::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Экспортировать данные', 'fas fa-upload', 'export')
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToUrl('<span data-bs-toggle="modal" data-bs-target="#import">Импортировать данные</span>', 'fas fa-download', '#')
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToUrl('API', 'fa fa-link', '/api')->setLinkTarget('_blank')
            ->setPermission('ROLE_ADMIN');

    }
}
