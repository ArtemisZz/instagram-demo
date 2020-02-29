<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/home", name="home_")
 */
class HomeController extends AbstractController
{
    /**
     * @Route("", name="home")
     * @param PostRepository $postRepository
     * @return RedirectResponse|Response
     */
    public function index(PostRepository $postRepository)
    {
        $posts = $postRepository->findAll();
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        return $this->render('home/index.html.twig', [
            'posts' => $posts,
        ]);
    }

}
