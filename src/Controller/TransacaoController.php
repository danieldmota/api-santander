<?php

namespace App\Controller;

use App\Dto\TransacaoDto;
use App\Entity\Transacao;
use App\Repository\ContaRepository;
use App\Repository\TransacaoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class TransacoesController extends AbstractController
{
    #[Route('/transacoes', name: 'transacoes_realizar', methods: ['POST'])]
    public function transferir(
        #[MapRequestPayload(acceptFormat: 'json')]
        TransacaoDto $transacaoDto,

        EntityManagerInterface $entityManager,
        ContaRepository $contaRepository
    ): JsonResponse {

        $erros = [];
        if (!$transacaoDto->getNumeroOrigem()) {
            $erros[] = ['message' => 'Conta de origem é obrigatória!'];
        }
        if (!$transacaoDto->getNumeroDestino()) {
            $erros[] = ['message' => 'Conta de destino é obrigatória!'];
        }
        if (!$transacaoDto->getValor() || $transacaoDto->getValor() <= 0) {
            $erros[] = ['message' => 'Valor da transação inválido!'];
        }

        if (count($erros) > 0) {
            return $this->json($erros, 422);
        }

        $contaOrigem = $contaRepository->findByNumero($transacaoDto->getNumeroOrigem());
        $contaDestino = $contaRepository->findByNumero($transacaoDto->getNumeroDestino());

        if (!$contaOrigem || !$contaDestino) {
            return $this->json(['message' => 'Conta de origem ou destino não encontrada!'], 404);
        }

        if ($contaOrigem->getSaldo() < $transacaoDto->getValor()) {
            return $this->json(['message' => 'Saldo insuficiente!'], 400);
        }

        // Realizar a transferência
        $contaOrigem->setSaldo($contaOrigem->getSaldo() - $transacaoDto->getValor());
        $contaDestino->setSaldo($contaDestino->getSaldo() + $transacaoDto->getValor());

        $transacao = new Transacao();
        $transacao->setContaOrigem($contaOrigem);
        $transacao->setContaDestino($contaDestino);
        $transacao->setValor($transacaoDto->getValor());
        $transacao->setData(new \DateTime());

        $entityManager->persist($transacao);
        $entityManager->flush();

        return $this->json(['message' => 'Transferência realizada com sucesso!'], 201);
    }
}
