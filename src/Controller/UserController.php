<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\ChangePasswordType;
use App\Form\PostType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("", name="profile_")
 */
class UserController extends AbstractController
{
    private $userType;
    private $usr;
    private $user;

    /**
     * UserController constructor.
     * @param UserRepository $userType
     * @param Security $security
     */
    public function __construct(UserRepository $userType, Security $security)
    {
        $this->userType = $userType;
        $this->usr = $security->getUser();
        $this->user = $this->userType->findOneBy(['email' => $this->usr->getUsername()]);
    }

    /**
     * @Route("/post", name="post")
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function postPost(EntityManagerInterface $entityManager, Request $request){
        $post = new Post();
        $post->setUser($this->user);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $upload = $form->get('image')->getData();
            if($upload){
                $newFileName = md5(uniqid().$upload->guessExtension());
                $upload->move(
                    $this->getParameter('uploads_directory'),
                    $newFileName
                );
                $post->setImage('uploads/'.$newFileName);
                $entityManager->persist($post);
                $entityManager->flush();
            }
            return $this->redirect($this->generateUrl('profile_profile',[
                'username' => $this->user->getPseudo()
            ]));
        }
        return $this->render('user/post.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/follow", name="follow")
     * @param int $id
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function follow(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager){
        $user = $userRepository->find($id);
        if(!$this->user){
            return $this->json('Failed', 403);
        }
        if($this->user->isFollowing($user)){
            $this->user->removeFollowing($user);
            $entityManager->persist($this->user);
            $entityManager->flush();

            return $this->json([
                'follower' => $user->getFollowers()->count(),
            ], 200);
        }
        else{
            $this->user->addFollowing($user);
            $entityManager->persist($this->user);
            $entityManager->flush();
            return $this->json([
                'follower' => $user->getFollowers()->count(),
            ], 200);

        }
    }

    /**
     * @Route("/{username}/changePassword", name="changePassword")
     * @param string $username
     * @param UserRepository $userRepository
     * @return Response
     */
    public function changePassword(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager){
        $user = $userRepository->findOneBy([
            'pseudo' =>$username
        ]);
        if($user === $this->user){
            $form = $this->createForm(ChangePasswordType::class, $user);

        }
        else{
            return new Response("<h1>You don't have the permisson to do this");
        }
    }

    /**
     * @Route("/{username}", name="profile")
     * @param UserRepository $userRepository
     * @param string $username
     * @return Response
     */
    public function index(UserRepository $userRepository, string $username)
    {
        $user = $userRepository->findOneBy([
            'pseudo' =>$username
        ]);
        if($user->getAvatar() === ''){
            $user->setAvatar('images/default_icon.png');
        }
        return $this->render('user/index.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/profile/edit", name="edit")
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $repository
     * @param Request $request
     * @return Response
     */
    public function editProfile(EntityManagerInterface $entityManager, UserRepository $repository,Request $request){
        $form = $this->createForm(UserType::class,$this->user);
        $this->user->confirm_password = $this->user->getPassword();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $avatar = $form->get('avatar')->getData();

            if($avatar){
                //$originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
                //$safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                //$newFilename = $safeFilename.'-'.uniqid().'.'.$avatar->guessExtension();
                $newFilename = md5(uniqid().$avatar->guessExtension());
                // Move the file to the directory where brochures are stored
                try {
                    $avatar->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $this->user->setAvatar('images/'.$newFilename);
                $entityManager->persist($this->user);
                $entityManager->flush();
            }
            return $this->redirect($this->generateUrl('profile_profile',[
                'username' => $this->user->getPseudo()
            ]));
        }

        return $this->render('user/edit.html.twig',[
            'form' => $form->createView()
        ]);
    }
}
