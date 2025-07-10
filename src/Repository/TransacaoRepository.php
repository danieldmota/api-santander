<?php

namespace App\Repository;

use App\Entity\Transacao;
use App\Entity\Conta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transacao>
 */
class TransacaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transacao::class);
    }


    public function findTransacoesDeSaida(Conta $conta): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.contaOrigem = :conta')
            ->setParameter('conta', $conta)
            ->orderBy('t.data', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Transações onde a conta foi o destino (entrada de dinheiro)
     */
    public function findTransacoesDeEntrada(Conta $conta): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.contaDestino = :conta')
            ->setParameter('conta', $conta)
            ->orderBy('t.data', 'DESC')
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Transacao[] Returns an array of Transacao objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transacao
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
