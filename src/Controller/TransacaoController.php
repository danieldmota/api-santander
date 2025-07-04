<?php

namespace App\Controller;


use App\Dto\UsuarioDto;
use App\Dto\TransacaoDto;
use App\Dto\TransacaoRealizarDto;
use App\Entity\Transacao;
use App\Repository\ContaRepository;
use App\Dto\UsuarioContaDto;
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
        UsuarioContaDto $usuarioContaDto,
        EntityManagerInterface $emi,
    ): Response
    {
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

        if ($entrada->getIdUsuarioOrigem() === $entrada->getIdUsuarioDestino()){
            array_push($erros, [
                'message' => 'As contas devem ser distintas!'
            ]);
        
        }

        $contaOrigem = $contaRepository->findByUsuarioId($entrada->getIdUsuarioOrigem());
        if (!$contaOrigem){
            return $this->json([
                'message' => 'Conta de origem não encontrada'
            ], 404);
        }

        $contaDestino = $contaRepository->findByUsuarioId($entrada->getIdUsuarioDestino());
        if (!$contaDestino){
            return $this->json([
                'message' => 'Conta de destino não encontrada'
            ], 404);
        }

        $saldoOrigem = (float)$contaOrigem->getSaldo();
        $saldoDestino = (float)$contaDestino->getSaldo();

        if($saldoOrigem < (float)$entrada->getValor()){
            return $this->json([
                'message' => 'Saldo insuficiente'
            ]);
        }

        if (count($erros) > 0){
            return $this->json($erros, 422);
        }
        $valor = (float)$entrada->getValor();

        $contaOrigem->setSaldo($saldoOrigem - $valor);
        $emi->persist($contaOrigem);
        
        $contaDestino->setSaldo($saldoDestino + $valor);
        $emi->persist($contaDestino);

        $transacao= new Transacao();
        $transacao->setDataHora(new DateTime());
        $transacao->setValor($entrada->getValor());
        $transacao->setContaOrigem($contaOrigem);
        $transacao->setContaDestino($contaDestino);

        $emi->persist($transacao);

        $emi->flush();
        return new Response(status: 201);
    }
}
