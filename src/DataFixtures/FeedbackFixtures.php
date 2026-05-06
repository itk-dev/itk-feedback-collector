<?php

namespace App\DataFixtures;

use App\Entity\Feedback;
use App\Entity\Website;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FeedbackFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $websiteFeedbackCounts = [
            WebsiteFixtures::WEBSITE_TEST_1 => 12,
            WebsiteFixtures::WEBSITE_TEST_2 => 10,
            WebsiteFixtures::WEBSITE_TEST_3 => 15,
        ];

        foreach ($websiteFeedbackCounts as $reference => $count) {
            /** @var Website $website */
            $website = $this->getReference($reference, Website::class);

            for ($i = 0; $i < $count; ++$i) {
                $feedback = new Feedback();
                $feedback->setWebsite($website);
                $feedback->setData([]);
                $manager->persist($feedback);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WebsiteFixtures::class,
        ];
    }
}
