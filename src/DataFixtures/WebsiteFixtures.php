<?php

namespace App\DataFixtures;

use App\Entity\Website;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WebsiteFixtures extends Fixture
{
    public const WEBSITE_DRUPAL = 'website-drupal';
    public const WEBSITE_SYMFONY = 'website-symfony';
    public const WEBSITE_TEST_1 = 'website-test-1';
    public const WEBSITE_TEST_2 = 'website-test-2';
    public const WEBSITE_TEST_3 = 'website-test-3';

    public function load(ObjectManager $manager): void
    {
        $websites = [
            [self::WEBSITE_DRUPAL, 'https://itk-feedback-drupal.local.itkdev.dk', 'itk-feedback-drupal'],
            [self::WEBSITE_SYMFONY, 'https://itk-feedback-symfony.local.itkdev.dk', 'itk-feedback-symfony'],
            [self::WEBSITE_TEST_1, 'https://www.example-one.dk', 'test-site-1'],
            [self::WEBSITE_TEST_2, 'https://www.example-two.dk', 'test-site-2'],
            [self::WEBSITE_TEST_3, 'https://www.example-three.dk', 'test-site-3'],
        ];

        foreach ($websites as [$reference, $url, $websiteId]) {
            $website = new Website();
            $website->setUrl($url);
            $website->setWebsiteId($websiteId);
            $website->generateApiKey();
            $manager->persist($website);
            $this->addReference($reference, $website);
        }

        $manager->flush();
    }
}
