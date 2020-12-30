<?php
namespace App\Controller;

use App\Entity\Program;
use App\Entity\Season;
use App\Entity\Episode;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\SearchProgramFormType;
use App\Repository\ProgramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ProgramType;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Slugify;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Form\CommentType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * @Route("/programs", name="program_")
 */
class ProgramController extends AbstractController
{
    /**
     * @Route("/", name="index")
     * @param Request $request
     * @param ProgramRepository $programRepository
     * @return Response
     */
    public function index(Request $request, ProgramRepository $programRepository): Response
    {
        $programs = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findAll();
        $searchForm = $this->createForm(SearchProgramFormType::class);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $search = $searchForm->getData()['search'];
            $programs = $programRepository->findLikeName($search);
        } else {
            $programs = $programRepository->findAll();
        }

        return $this->render('program/index.html.twig', [
            'programs' => $programs,
            'form'=> $searchForm->createView(),
        ]);
    }

    /**
     * The controller for the program add form
     *
     * @Route("/new", name="new")
     * @param Request $request
     * @param Slugify $slugify
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function new(Request $request, Slugify $slugify, MailerInterface $mailer): Response
    {
        $program = new Program();
        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $slug = $slugify->generate($program->getTitle());
            $program->setSlug($slug);
            $program->setOwner($this->getUser());
            $entityManager->persist($program);
            $entityManager->flush();
            $this->addFlash('success', 'The program has benn edited');

            $email = (new Email())
                ->from($this->getParameter('mailer_from'))
                ->to('test@test.com')
                ->subject('Une nouvelle série vient d\'être publiée !')
                ->html($this->renderView('program/newProgramEmail.html.twig', ['program' => $program]));
            $mailer->send($email);
            return $this->redirectToRoute('program_index');
        }
        return $this->render('program/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="edit", methods={"GET","POST"})
     * @ParamConverter("program", class="App\Entity\Program", options={"mapping": {"slug": "slug"}})
     * @param Request $request
     * @param Program $program
     * @return Response
     */
    public function edit(Request $request, Program $program): Response
    {
        if (!($this->getUser() == $program->getOwner()))
        {
            throw new AccessDeniedException('Only the owner can edit the program!');
        }
        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The program has ben edited');

            return $this->redirectToRoute('program_index');
        }

        return $this->render('program/edit.html.twig', [
            'program' => $program,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", methods={"GET"}, name="show")
     * @param Program $program
     * @return Response
     */

    public function show(Program $program): Response
    {
        if (!$program) {
            throw $this->createNotFoundException(
                'No program with id : found in program\'s table.'
            );
        }

        $seasons = $program->getSeasons();
        return $this->render('program/show.html.twig', [
            'program' => $program,
            'seasons' => $seasons
        ]);
    }

    /**
     * @Route("/{program_slug}/season/{season<^[0-9]+$>}", methods={"GET"}, name="season_show")
     * @ParamConverter("program", class="App\Entity\Program", options={"mapping": {"program_slug": "slug"}})
     * @param Program $program
     * @param Season $season
     * @return Response
     */
    public function showSeason(Program $program, Season $season): Response
    {
        if (!$program) {
            throw $this->createNotFoundException(
                'No program with id : found in program\'s table.'
            );
        }
        if (!$season) {
            throw $this->createNotFoundException(
                'No season with id : found in season\'s table.'
            );
        }

        return $this->render('program/season_show.html.twig', [
            'program' => $program,
            'season' => $season
        ]);
    }

    /**
     * @Route("/{program_slug}/seasons/{season<^[0-9]+$>}/episodes/{episode_slug}", methods={"GET", "POST"}, name="episode_show")
     * @ParamConverter("program", class="App\Entity\Program", options={"mapping": {"program_slug": "slug"}})
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episode_slug": "slug"}})
     * @param Program $program
     * @param Season $season
     * @param Episode $episode
     * @param Request $request
     * @return Response
     */
    public function showEpisode(Program $program, Season $season, Episode $episode, Request $request): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $comment->setEpisode($episode);
            $comment->setAuthor($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();
            return $this->redirectToRoute('program_index');
        }
        return $this->render('program/episode_show.html.twig', [
            'program' => $program,
            'season' => $season,
            'episode' => $episode,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{commentId}", name="comment_delete", methods={"DELETE"})
     * @ParamConverter("comment", class="App\Entity\Comment", options={"mapping": {"commentId": "id"}})
     * @param Comment $comment
     * @param Request $request
     * @return Response
     */
    public function deleteComment(Comment $comment, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('program_index');
    }

    /**
     * @Route("/{slug}/watchlist",
     *     methods={"GET","POST"}, name="watchlist")
     * @param Program $program
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addToWatchlist(Program $program, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()->getWatchlist()->contains($program)) $this->getUser()->removeWatchlist($program); else {
            $this->getUser()->addWatchlist($program);
        }
        $entityManager->flush();

        return $this->json([
            'isInWatchlist' => $this->getUser()->isInWatchlist($program)
        ]);
    }
}

