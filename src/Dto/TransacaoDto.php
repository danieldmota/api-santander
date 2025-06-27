<?php
namespace App\Dto;

class TransacaoDto
{
    public int $id_conta_origem;
    public int $id_conta_destino;
    public float $valor;

    public function getIdContaOrigem(): int { return $this->id_conta_origem; }
    public function getIdContaDestino(): int { return $this->id_conta_destino; }
    public function getValor(): float { return $this->valor; }
}
