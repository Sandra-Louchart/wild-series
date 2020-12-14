<?php

namespace App\DataFixtures;

use App\Entity\Episode;
use Faker;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Service\Slugify;

class EpisodeFixtures extends Fixture implements DependentFixtureInterface
{
    private $slugify;

    public function __construct(Slugify $slugify)
    {
        $this->slugify=$slugify;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        for ($i=0; $i<100; $i++) {
            $episode = new Episode();
            $episode->setNumber($faker->numberBetween(1, 15));
            $episode->setTitle($faker->sentence(6));
            $episode->setSlug($this->slugify->generate($episode->getTitle()));
            $episode->setSynopsis($faker->text(400));
            $episode->setSeason($this->getReference('season_' . rand(0,19)));
            $manager->persist($episode);
            $this->addReference('episode_' . $i, $episode);
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [SeasonFixtures::class];
    }
}