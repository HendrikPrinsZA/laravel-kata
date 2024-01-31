<?php

namespace App\Utilities;

use Illuminate\Support\Str;
use ReflectionMethod;

class CodeUtility
{
    public static function getCodeSnippet(
        ReflectionMethod $reflectionMethod,
        ?int $limit = null
    ): string {
        $fileName = $reflectionMethod->getFileName();
        $startLine = $reflectionMethod->getStartLine() - 1;
        $endLine = $reflectionMethod->getEndLine();
        $length = $endLine - $startLine;
        $lines = file($fileName);

        $code = collect(array_slice($lines, $startLine, $length))
            ->map(fn ($line) => substr($line, 4))
            ->map(fn ($line) => is_null($limit) ? $line : Str::limit($line, 80))
            ->join('');

        return $code;
    }

    public static function getCodeMd5(ReflectionMethod $reflectionMethod): string
    {
        $code = self::getCodeSnippet($reflectionMethod);

        return md5($code);
    }
}
