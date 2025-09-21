<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use RuntimeException;
use Throwable;

class FaultException extends RuntimeException
{
    /**
     * Layer-scoped context.
     *
     * @var array<string, mixed>
     */
    public private(set) array $context = [];

    /**
     * Layer-scoped internal messages.
     *
     * @var list<string>
     */
    public private(set) array $internalMessages = [];

    /**
     * Layer-scoped origin (file and line).
     *
     * @var null|array{file: string, line: int}
     */
    public private(set) null|array $origin = null;

    /**
     * Layer-scoped public messages.
     *
     * @var list<string>
     */
    public private(set) array $publicMessages = [];

    /**
     * Layer-scoped code identifier.
     */
    private null|int|string $codeId = null;

    /**
     * Create a new fault instance, optionally wrapping a previous throwable.
     */
    public function __construct(string $internalMessage = '', null|Throwable $previous = null)
    {
        parent::__construct('', 0, $previous);

        if ($internalMessage !== '') {
            $this->addInternal($internalMessage);
        } else {
            $this->refreshMessage();
        }
    }

    /**
     * Add context to this layer.
     *
     * @param iterable<mixed> $context
     */
    public function addContext(iterable $context): void
    {
        foreach ($context as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if ($key === '') {
                continue;
            }

            $this->context[$key] = $value;
        }
    }

    /**
     * Add an internal message to this layer.
     */
    public function addInternal(string $message): void
    {
        if ($message === '') {
            return;
        }

        array_unshift($this->internalMessages, $message);

        $this->refreshMessage();
    }

    /**
     * Add a public-facing message to this layer.
     */
    public function addPublic(string $message): void
    {
        if ($message === '') {
            return;
        }

        array_unshift($this->publicMessages, $message);
    }

    /**
     * Get the code identifier for this layer.
     */
    public function code(): null|int|string
    {
        return $this->codeId;
    }

    /**
     * Set the code identifier for this layer.
     */
    public function setCode(int|string $code): void
    {
        $this->codeId = $code;
    }

    /**
     * Set the call-site origin for this layer.
     *
     * @param array{file: string, line: int} $origin
     */
    public function setOrigin(array $origin): void
    {
        $file = $origin['file'];
        $line = $origin['line'];

        if ($file === '' || $line <= 0) {
            return;
        }

        $this->origin = [
            'file' => $file,
            'line' => $line,
        ];
    }

    /**
     * Build the internal message chain for this layer.
     *
     * @return list<string>
     */
    private function getInternalChain(): array
    {
        $messages = $this->internalMessages;
        $previous = $this->getPrevious();

        if ($previous instanceof self) {
            $messages = array_merge($messages, $previous->getInternalChain());
        } elseif ($previous instanceof Throwable) {
            $previousMessage = $previous->getMessage();

            if ($previousMessage !== '') {
                $messages[] = $previousMessage;
            }
        }

        return $messages;
    }

    /**
     * Recompute this layer's message from the internal chain.
     */
    private function refreshMessage(): void
    {
        $chain = $this->getInternalChain();

        if ($chain === []) {
            $this->message = '';

            return;
        }

        $this->message = implode(': ', $chain);
    }
}
