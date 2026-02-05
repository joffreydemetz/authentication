<?php

declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Contract;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
    public function verify(string $plainPassword, string $hashedPassword): bool;
    public function needsRehash(string $hashedPassword): bool;
}
