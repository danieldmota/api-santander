<?php

namespace App\Controller;

use App\Dto\ContaDto;
use App\Dto\UsuarioDto;
use Symfony\Component\HttpFoundation\Request;
use App\Dto\TransacaoDto;
use App\Dto\TransacaoExtratoDto;
use App\Dto\TransacaoRealizarDto;
use App\Dto\TransacoesExtratoDto;
use App\Entity\Transacao;
use App\Repository\ContaRepository;
use App\Dto\UsuarioContaDto;
use App\Entity\Conta;
use App\Repository\TransacaoRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class TransacaoController extends AbstractController
{
    #[Route('/transacoes', name: 'transacoes_realizar', methods: ['POST'])]
    public function realizar(
        #[MapRequestPayload(acceptFormat: 'json')]
        TransacaoRealizarDto $entrada,
        ContaRepository $contaRepository,
        EntityManagerInterface $emi,
    ): Response {
        $erros = [];
        // validar os dados do DTO
        if (!$entrada->getIdUsuarioOrigem()) {
            array_push($erros, [
                'message' => 'Conta de origem é obrigatória!'
            ]);
        }
        if (!$entrada->getIdUsuarioDestino()) {
            array_push($erros, [
                'message' => 'Conta de destino é obrigatória!'
            ]);
        }
        if (!$entrada->getValor() || (float)$entrada->getValor() <= 0) {
            array_push($erros, [
                'message' => 'Valor deve ser valido!'
            ]);
        }

        if ($entrada->getIdUsuarioOrigem() === $entrada->getIdUsuarioDestino()) {
            array_push($erros, [
                'message' => 'As contas devem ser distintas!'
            ]);
        }

        $contaOrigem = $contaRepository->findByUsuarioId($entrada->getIdUsuarioOrigem());
        if (!$contaOrigem) {
            return $this->json([
                'message' => 'Conta de origem não encontrada'
            ], 404);
        }

        $contaDestino = $contaRepository->findByUsuarioId($entrada->getIdUsuarioDestino());
        if (!$contaDestino) {
            return $this->json([
                'message' => 'Conta de destino não encontrada'
            ], 404);
        }

        $saldoOrigem = (float)$contaOrigem->getSaldo();
        $saldoDestino = (float)$contaDestino->getSaldo();

        if ($saldoOrigem < (float)$entrada->getValor()) {
            return $this->json([
                'message' => 'Saldo insuficiente'
            ]);
        }

        if (count($erros) > 0) {
            return $this->json($erros, 422);
        }
        $valor = (float)$entrada->getValor();

        $contaOrigem->setSaldo($saldoOrigem - $valor);
        $emi->persist($contaOrigem);

        $contaDestino->setSaldo($saldoDestino + $valor);
        $emi->persist($contaDestino);

        $transacao = new Transacao();
        $transacao->setDataHora(new DateTime());
        $transacao->setValor($entrada->getValor());
        $transacao->setContaOrigem($contaOrigem);
        $transacao->setContaDestino($contaDestino);
        $emi->persist($transacao);

        $emi->flush();

        $transacaoDto= $this->converterTransacaoExtratoDto($transacao, $contaOrigem);

        return $this->json($transacaoDto, status: 201);
    }

    #[Route('/transacoes/{idUsuario}/extrato', name: 'transacoes_extrato', methods: ['GET'])]
    public function extrato(
        int $idUsuario,
        ContaRepository $contaRepository,
        TransacaoRepository $transacaoRepository
    ): JsonResponse {
        $conta = $contaRepository->findByUsuarioId($idUsuario);
        if (!$conta) {
            return $this->json(['message' => 'Usuário não encontrado'], 404);
        }


        $transacoes = $transacaoRepository->findByContaOrigemOrContaDestino($conta->getId());

        $saida = [];

        foreach ($transacoes as $transacao) {
            $transacaoDto= $this->converterTransacaoExtratoDto($transacao, $conta);
            array_push($saida, $transacaoDto);
        }


        return $this->json($saida);
    }

    private function converterTransacaoExtratoDto(
        Transacao $transacao, 
        Conta $conta
        ) : TransacaoExtratoDto
    {
        $transacaoDto = new TransacaoExtratoDto();
        $transacaoDto->setId($transacao->getId());
        $transacaoDto->setValor($transacao->getValor());
        $transacaoDto->setDataHora($transacao->getDataHora());

        if ($conta->getId() === $transacao->getContaOrigem()->getId()) {
            $transacaoDto->setTipo('Enviou');
        } else if ($conta->getId() === $transacao->getContaDestino()->getId()) {
            $transacaoDto->setTipo("Recebeu");
        }

        $origem = $transacao->getContaOrigem();
        $contaOrigemDto = new ContaDto();
        $contaOrigemDto->setId($origem->getUsuario()->getId());
        $contaOrigemDto->setNome($origem->getUsuario()->getNome());
        $contaOrigemDto->setCpf($origem->getUsuario()->getCpf());
        $contaOrigemDto->setNumeroConta($origem->getNumero());
        $transacaoDto->setOrigem($contaOrigemDto);

        $destino = $transacao->getContaDestino();
        $contaDestinoDto = new ContaDto();
        $contaDestinoDto->setId($destino->getUsuario()->getId());
        $contaDestinoDto->setNome($destino->getUsuario()->getNome());
        $contaDestinoDto->setCpf($destino->getUsuario()->getCpf());
        $contaDestinoDto->setNumeroConta($destino->getNumero());

        $transacaoDto->setDestino($contaDestinoDto);

        return $transacaoDto;
    }
}
