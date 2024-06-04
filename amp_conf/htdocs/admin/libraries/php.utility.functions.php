<?php
use Symfony\Component\Process\Process;
/*** 
 * in php 8+ version htmlspecialchars() will not accept null argument
 * so this function is replacements to check null values
*/
function freepbx_htmlspecialchars($str)
{
    return htmlspecialchars($str ?? '');
}

/*** 
 * in php 8+ version trim() will not accept null argument
 * so this function is replacements to check null values
*/
function freepbx_trim($str)
{
    return trim($str ?? '');
}

function freepbx_str_replace($w, $r, $s)
{
    if($s && $w && $r) {
        return str_replace($w, $r, $s);
    } else {
        return $s;
    }
}

function freepbx_strftime($format, $time=false)
{
    if($format) {
        return ($time) ? date($format, $time) : date($format);
    } else {
        return ($time) ? date('m/d/Y H:i:s', $time) : $time;
    }
}
/***
 * Creates a new Process object based on the given command, taking into account shell features.
 *
 * @param array|string $command The command as an array of arguments or a shell command string.
 * @param string|null $cwd The working directory to use for the process.
 * @param array|null $env The environment variables to use for the process.
 * @param mixed $input The input to be passed to the process.
 * @param float|null $timeout The timeout duration in seconds.
 * @return Process The Process object representing the command.
 */
function freepbx_get_process_obj($command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60) 
{
    try {
        // Using an array of arguments is the recommended way to define commands in Process.
        // This saves us from any escaping and allows sending signals seamlessly.
        if (is_array($command)) {
            return new Process($command, $cwd, $env, $input, $timeout);
        }

        // If we need to use stream redirections, conditional execution,
        // or any other feature provided by the shell of our operating system,
        // we can also define commands as strings using the fromShellCommandline() static factory.
        if (checkShellFeatures($command)) {
            return Process::fromShellCommandline($command, $cwd, $env, $input, $timeout);
        }

        // If the command doesn't contain shell features, split the command string into an array of arguments.
        $commandArray = splitShellCommand($command);
        return new Process($commandArray, $cwd, $env, $input, $timeout);
    } catch (Exception $e) {
        // Log the exception message for debugging purposes
        dbug('Error in \freepbx_get_process_obj(): ' . $e->getMessage());
        return null;
    }
}
/**
 * Checks if a shell command string contains any stream redirections, conditional execution, or other shell features.
 * 
 * The regular expression pattern [|&;<>] matches the characters |, &, ;, <, and >, 
 * which commonly represent stream redirections, conditional execution, and other shell features.
 * 
 * @param string $command The shell command string to check.
 * @return bool Returns true if shell features are present, false otherwise.
 */
function checkShellFeatures($command) 
{
    $pattern = '/[|&;<>]/'; 
    if (preg_match($pattern, $command)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Splits a shell command string into an array of individual components.
 *
 * @param string $command The shell command string to split.
 * @return array An array of individual components of the shell command.
 */
function splitShellCommand($command)
{
    $pattern = '/\s+(?=(?:(?:[^\'"]*[\'"]){2})*[^\'"]*$)/';
    $parts = preg_split($pattern, $command);
    // Remove quotes from values
    $parts = array_map(function ($part) {
            return str_replace(['"', "'"], '', $part);
    }, $parts);

    return array_filter($parts);
}


/**
 * php-utf8-en-decode-deprecated 
 *
 * @param input string 
 * @return converted string 
 */
function freepbx_utf8_decode($text)
{
    return mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));
}
