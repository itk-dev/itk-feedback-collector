<?php

namespace App\DataFixtures;

use App\Entity\Website;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WebsiteFixtures extends Fixture
{
    public const WEBSITE_AARHUS = 'website-aarhus';
    public const WEBSITE_MKB = 'website-mkb';
    public const WEBSITE_DOKK1 = 'website-dokk1';

    public function load(ObjectManager $manager): void
    {
        $websites = [
            [self::WEBSITE_AARHUS, 'https://www.aarhus.dk', 'aarhus-dk'],
            [self::WEBSITE_MKB, 'https://www.mkb.aarhus.dk', 'mkb-aarhus'],
            [self::WEBSITE_DOKK1, 'https://www.dokk1.dk', 'dokk1-dk'],
        ];

        foreach ($websites as [$reference, $url, $websiteId]) {
            $website = new Website();
            $website->setUrl($url);
            $website->setWebsiteId($websiteId);
            $manager->persist($website);
            $this->addReference($reference, $website);
        }

        $manager->flush();
    }
}
