<?php


namespace schedule\scheduling;


use schedule\ProcessUtils;

class CommandBuilder
{
    /**
     * Build the command for the given event.
     * @param Event $event
     * @return string
     */
    public function buildCommand(Event $event)
    {
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);
        }
        return $this->buildForegroundCommand($event);
    }

    /**
     * Build the command for running the event in the background.
     *
     * @param Event $event
     * @return string
     */
    protected function buildBackgroundCommand(Event $event)
    {
        $output = ProcessUtils::escapeArgument($event->output);

        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';

        if (PHP_OS === 'Windows') {
            return 'start /b cmd /v:on /c "(' . $event->command . ' &  ^!ERRORLEVEL^!)' . $redirect . $output . ' 2>&1"';
        }

        return $this->ensureCorrectUser($event, $event->command . $redirect . $output . ' 2>&1 &');
    }


    /**
     * Build the command for running the event in the foreground.
     * @param Event $event
     * @return string
     */
    protected function buildForegroundCommand(Event $event)
    {
        $output = ProcessUtils::escapeArgument($event->output);
        return $this->ensureCorrectUser(
            $event, $event->command . ($event->shouldAppendOutput ? ' >> ' : ' > ') . $output . ' 2>&1'
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     * @param Event $event
     * @param string $command
     * @return string
     */
    protected function ensureCorrectUser(Event $event, $command)
    {
        return $event->user && !(PHP_OS === 'Windows') ? 'sudo -u ' . $event->user . ' -- sh -c \'' . $command . '\'' : $command;
    }
}