<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProfileContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfileContent>
 */
class ProfileContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, ProfileContent::class);
    }

    public function getOrCreate(): ProfileContent
    {
        $profile = $this->find(1);
        if (!$profile) {
            $profile = new ProfileContent();
            $this->em->persist($profile);
            $this->em->flush();
        }
        return $profile;
    }
}
