<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Input;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;

/**
 * Represents a command line argument.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InputArgument
{
    public const REQUIRED = 1;
    public const OPTIONAL = 2;
    public const IS_ARRAY = 4;

    private int $mode;
    private string|int|bool|array|float|null $default;

    /**
     * @param string                                                                        $name            The argument name
     * @param int|null                                                                      $mode            The argument mode: a bit mask of self::REQUIRED, self::OPTIONAL and self::IS_ARRAY
     * @param string                                                                        $description     A description text
     * @param string|bool|int|float|array|null                                              $default         The default value (for self::OPTIONAL mode only)
     * @param array|\Closure(CompletionInput,CompletionSuggestions):list<string|Suggestion> $suggestedValues The values used for input completion
     *
     * @throws InvalidArgumentException When argument mode is not valid
     */
    public function __construct(
        private string $name,
        int $mode = null,
        private string $description = '',
        string|bool|int|float|array $default = null,
        private \Closure|array $suggestedValues = [],
    ) {
        if (null === $mode) {
            $mode = self::OPTIONAL;
        } elseif ($mode > 7 || $mode < 1) {
            throw new InvalidArgumentException(sprintf('Argument mode "%s" is not valid.', $mode));
        }

        $this->mode = $mode;

        $this->setDefault($default);
    }

    /**
     * Returns the argument name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns true if the argument is required.
     *
     * @return bool true if parameter mode is self::REQUIRED, false otherwise
     */
    public function isRequired(): bool
    {
        return self::REQUIRED === (self::REQUIRED & $this->mode);
    }

    /**
     * Returns true if the argument can take multiple values.
     *
     * @return bool true if mode is self::IS_ARRAY, false otherwise
     */
    public function isArray(): bool
    {
        return self::IS_ARRAY === (self::IS_ARRAY & $this->mode);
    }

    /**
     * Sets the default value.
     *
     * @throws LogicException When incorrect default value is given
     */
    public function setDefault(string|bool|int|float|array|null $default): void
    {
        if ($this->isRequired() && null !== $default) {
            throw new LogicException('Cannot set a default value except for InputArgument::OPTIONAL mode.');
        }

        if ($this->isArray()) {
            if (null === $default) {
                $default = [];
            } elseif (!\is_array($default)) {
                throw new LogicException('A default value for an array argument must be an array.');
            }
        }

        $this->default = $default;
    }

    /**
     * Returns the default value.
     */
    public function getDefault(): string|bool|int|float|array|null
    {
        return $this->default;
    }

    public function hasCompletion(): bool
    {
        return [] !== $this->suggestedValues;
    }

    /**
     * Adds suggestions to $suggestions for the current completion input.
     *
     * @see Command::complete()
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        $values = $this->suggestedValues;
        if ($values instanceof \Closure && !\is_array($values = $values($input))) {
            throw new LogicException(sprintf('Closure for argument "%s" must return an array. Got "%s".', $this->name, get_debug_type($values)));
        }
        if ($values) {
            $suggestions->suggestValues($values);
        }
    }

    /**
     * Returns the description text.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
