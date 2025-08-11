<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof User) {
            throw new \InvalidArgumentException('Expected User instance');
        }

        // Valider les données
        $errors = $this->validator->validate($data, null, ['user:create']);
        if (count($errors) > 0) {
            throw new \RuntimeException((string) $errors);
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $data,
            $data->getPassword()
        );
        $data->setPassword($hashedPassword);

        // Définir le rôle par défaut
        if (empty($data->getRoles())) {
            $data->setRoles(['ROLE_USER']);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}