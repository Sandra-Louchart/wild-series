<?php


namespace App\Controller;

use App\Entity\Category;
use App\Entity\Program;
use App\Repository\CategoryRepository;
use App\Repository\ProgramRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CategoryController
 * @package App\Controller
 * @Route("/categories", name="category_")
 */
class CategoryController extends AbstractController
{
    /**
     * Show all rows from Category's entity
     *
     * @Route("/", name="index")
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    public function index(CategoryRepository $categoryRepository) :Response
    {
        $categories = $categoryRepository->findAll();
        return $this->render('category/index.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * Getting all programs by category's name
     *
     * @Route("/{categoryName<^[a-zA-Z]+$>}", methods={"GET"}, name="show")
     * @param string $categoryName
     * @param CategoryRepository $categoryRepository
     * @param ProgramRepository $programRepository
     * @return Response
     */
    public function show(string $categoryName, CategoryRepository $categoryRepository, ProgramRepository $programRepository): Response
    {
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findOneBy(['name' => $categoryName]);
        $programs = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findBy(
                ['category' => $category],
                ['id' => 'DESC'],
                3);
        if (!$programs) {
            throw $this->createNotFoundException(
                'No program in : ' . $categoryName . ' category found in program\'s table.'
            );
        }
        return $this->render('category/show.html.twig', [
            'programs' => $programs,
            'category' => $category
        ]);
    }
}