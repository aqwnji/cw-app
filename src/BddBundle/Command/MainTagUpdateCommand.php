<?php

namespace BddBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MainTagUpdateCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * MainTagUpdateCommand constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('main-tag:update')
            ->setDescription('Update main tags for all coasters');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = $this->em->getConnection();
        $coasters = $this->em->getRepository('BddBundle:Coaster')->findAll();

        $sql = 'truncate table main_tag';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        foreach ($coasters as $coaster) {
            $conn = $this->em->getConnection();

            $sql = 'SELECT t.id, count(*) AS nb FROM ridden_coaster r 
            INNER JOIN ridden_coaster_con rc ON rc.ridden_coaster_id = r.id
            INNER JOIN tag t ON t.id = rc.tag_id
            WHERE coaster_id = :coasterId
            GROUP BY t.id
            UNION
            SELECT t.id, count(*) AS nb FROM ridden_coaster r 
            INNER JOIN ridden_coaster_pro rp ON rp.ridden_coaster_id = r.id
            INNER JOIN tag t ON t.id = rp.tag_id
            WHERE coaster_id = :coasterId
            GROUP BY t.id
            ORDER BY nb DESC';

            $stmt = $conn->prepare($sql);
            $stmt->execute(['coasterId' => $coaster->getId()]);

            $rank = 1;
            foreach ($stmt->fetchAll() as $mainTag) {
                if ($rank > 3) {
                    break;
                }
                $sql = 'INSERT INTO `main_tag` (coaster_id, tag_id, rank) VALUES (:coasterId, :tagId, :rank)';
                $stmt = $conn->prepare($sql);
                $stmt->execute(['coasterId' => $coaster->getId(), 'tagId' => (int)$mainTag['id'], 'rank' => $rank]);
                $rank++;
            }
        }
    }
}
