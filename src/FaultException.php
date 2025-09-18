<?php

declare(strict_types=1);

namespace HosmelQ\Fault;

use RuntimeException;
use Throwable;

final class FaultException extends RuntimeException
{
    /**
     * The URN captured for this level of the fault chain.
     */
    private null|string $faultCode = null;

    /**
     * @var list<string> Ordered newest → oldest internal messages for this layer.
     */
    private array $internalMessages = [];

    /**
     * @var list<string> Ordered newest → oldest public messages for this layer.
     */
    private array $publicMessages = [];

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
     * Append a new internal detail to the current fault.
     */
    public function addInternal(string $message): void
    {
        $message = mb_trim($message);

        if ($message === '') {
            return;
        }

        array_unshift($this->internalMessages, $message);
        $this->refreshMessage();
    }

    /**
     * Append a new public-facing message to the current fault.
     */
    public function addPublic(string $message): void
    {
        $message = mb_trim($message);

        if ($message === '') {
            return;
        }

        array_unshift($this->publicMessages, $message);
    }

    /**
     * Resolve the first fault URN in the wrapped chain.
     */
    public function code(): null|string
    {
        if ($this->faultCode !== null) {
            return $this->faultCode;
        }

        $previous = $this->getPrevious();

        if ($previous instanceof self) {
            return $previous->code();
        }

        return null;
    }

    /**
     * Gather internal messages from this layer and its chain.
     *
     * @return list<string>
     */
    public function getInternalChain(): array
    {
        $messages = $this->internalMessages;

        $previous = $this->getPrevious();

        if ($previous instanceof self) {
            $messages = array_merge($messages, $previous->getInternalChain());
        } elseif ($previous instanceof Throwable) {
            $previousMessage = mb_trim($previous->getMessage());

            if ($previousMessage !== '') {
                $messages[] = $previousMessage;
            }
        }

        return array_values(array_filter($messages, static fn (string $value): bool => $value !== ''));
    }

    /**
     * Gather public messages from this layer and its chain.
     *
     * @return list<string>
     */
    public function getPublicChain(): array
    {
        $messages = $this->publicMessages;

        $previous = $this->getPrevious();

        if ($previous instanceof self) {
            $messages = array_merge($messages, $previous->getPublicChain());
        }

        return array_values(array_filter($messages, static fn (string $value): bool => $value !== ''));
    }

    /**
     * Record the fault URN for the current layer when one has not been set yet.
     */
    public function setCode(string $code): void
    {
        $code = mb_trim($code);

        if ($code === '' || $this->faultCode !== null) {
            return;
        }

        $this->faultCode = $code;
    }

    /**
     * Concatenate user-facing messages gathered from the entire chain.
     */
    public function userFacingMessage(): string
    {
        $messages = $this->getPublicChain();

        return implode(' ', $messages);
    }

    /**
     * Recompute the Exception message from the collected internal chain.
     */
    private function refreshMessage(): void
    {
        $this->message = implode(': ', $this->getInternalChain());
    }
}
