<?php

namespace App\Controller;


use App\Dto\UsuarioDto;
use App\Dto\TransacaoDto;
use App\Dto\TransacaoRealizarDto;
use App\Entity\Transacao;
use App\Repository\ContaRepository;
use App\Dto\UsuarioContaDto;
use App\Repository\TransacaoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        UsuarioContaDto $usuarioContaDto
    ): JsonResponse
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

        if (count($erros) > 0){
            return $this->json($erros, 422);
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

        return $this->json("Hello World");
    }
}
