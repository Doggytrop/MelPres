<?php

namespace App\Helpers;

class NumberHelper
{
    public static function toWords(float $amount): string
    {
        $amount = round($amount, 2);
        $entero = (int) $amount;
        $centavos = round(($amount - $entero) * 100);

        $texto = self::convertir($entero);

        if ($centavos > 0) {
            $texto .= ' pesos con ' . self::convertir($centavos) . ' centavos';
        } else {
            $texto .= ' pesos';
        }

        return strtoupper($texto);
    }

    private static function convertir(int $n): string
    {
        if ($n === 0) return 'cero';

        $unidades  = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
                      'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis',
                      'diecisiete', 'dieciocho', 'diecinueve'];
        $decenas   = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta',
                      'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas  = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos',
                      'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        $resultado = '';

        if ($n >= 1000000) {
            $millones = (int)($n / 1000000);
            $resultado .= ($millones === 1 ? 'un millón' : self::convertir($millones) . ' millones') . ' ';
            $n %= 1000000;
        }

        if ($n >= 1000) {
            $miles = (int)($n / 1000);
            $resultado .= ($miles === 1 ? 'mil' : self::convertir($miles) . ' mil') . ' ';
            $n %= 1000;
        }

        if ($n >= 100) {
            $resultado .= ($n === 100 ? 'cien' : $centenas[(int)($n / 100)]) . ' ';
            $n %= 100;
        }

        if ($n >= 20) {
            $resultado .= $decenas[(int)($n / 10)];
            if ($n % 10 > 0) $resultado .= ' y ' . $unidades[$n % 10];
            $resultado .= ' ';
        } elseif ($n > 0) {
            $resultado .= $unidades[$n] . ' ';
        }

        return trim($resultado);
    }
}