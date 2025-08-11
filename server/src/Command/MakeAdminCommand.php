<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:make-admin',
    description: 'Ajoute le rôle admin à un utilisateur',
)]
class MakeAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'email', 
                InputArgument::REQUIRED, 
                'Email de l\'utilisateur'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('<error>Utilisateur non trouvé</error>');
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $output->writeln('<comment>L\'utilisateur est déjà admin</comment>');
            return Command::SUCCESS;
        }

        $roles[] = 'ROLE_ADMIN';
        $user->setRoles(array_unique($roles));
        $this->em->flush();

        $output->writeln(sprintf(
            '<info>Rôle admin ajouté à %s</info>',
            $email
        ));

        return Command::SUCCESS;
    }
}