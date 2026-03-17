<?php

namespace ChangelogCLI;

use Illuminate\Support\Facades\Storage;

class Changelog
{
    public const ADDED = 'New feature';
    public const FIXED = 'Bug fix';
    public const CHANGED = 'Feature change';
    public const DEPRECATED = 'New deprecation';
    public const REMOVED = 'Feature removal';
    public const SECURITY = 'Security fix';

    public $menuName = 'Changelog category';

    public $menuItems = [
        'New feature',
        'Bug fix',
        'Feature change',
        'New deprecation',
        'Feature removal',
        'Security fix',
    ];

    public array $categories = [
        'Added',
        'Changed',
        'Deprecated',
        'Fixed',
        'Removed',
        'Security',
    ];

    /**
     * Returns registered git username.
     */
    public function gitUsername(): string
    {
        return exec('git config user.name');
    }

    /**
     * Returns active git branch name.
     */
    public function branchName(): string
    {
        return exec('git symbolic-ref --short HEAD');
    }

    /**
     * Returns unreleased path for changelog files.
     */
    public function unreleasedPath(): string
    {
        return config('app.structure.unreleased');
    }

    public function filePath(): string
    {
        return $this->unreleasedPath() . DIRECTORY_SEPARATOR . $this->branchName() . '.md';
    }

    public function execute($option)
    {
        if (!Storage::exists($this->unreleasedPath())) {
            Storage::makeDirectory($this->unreleasedPath());
        }

        $content = "---\n";
        $content.= "author: ".$this->gitUsername()."\n";
        $content.= "date: ".now()."\n";
        $content.= "---\n\n";

        switch ($this->menuItems[$option]) {
            case self::ADDED:
                $content.= "### Added\n";
                break;
            case self::CHANGED:
                $content.= "### Changed\n";
                break;
            case self::DEPRECATED:
                $content.= "### Deprecated\n";
                break;
            case self::FIXED:
                $content.= "### Fixed\n";
                break;
            case self::REMOVED:
                $content.= "### Removed\n";
                break;
            case self::SECURITY:
                $content.= "### Security\n";
                break;
            default:
                break;
        }

        return Storage::put($this->filePath(), $content);
    }

    /**
     * Search text in file return line number.
     *
     * @param string $category
     * @param string $file
     * @return int
     */
    public function search(string $category, string $file): int
    {
        $line = exec("grep -n -m 1 '{$category}' {$file} | cut -d : -f 1");

        return (int)$line;
    }

    /**
     * Using vim ex mod writing text on specific line.
     *
     * @param string $text
     * @param int $line
     * @param string $file
     * @return string
     */
    public function writeAfterLine(string $text, int $line, string $file): string
    {
        $lineNumber = $line + 1;
        return exec("ex -sc '{$lineNumber}i|{$text}' -cx {$file}");
    }

    public function appendCategories(): void
    {
        $line = $this->search('Unreleased', config('app.structure.main'));

        $content = "### Added\n";
        $content.= "### Changed\n";
        $content.= "### Deprecated\n";
        $content.= "### Fixed\n";
        $content.= "### Removed\n";
        $content.= "### Security\n";

        $this->writeAfterLine($content, $line, config('app.structure.main'));
    }

    /**
     * Returns content between two string in file.
     *
     * @param $start
     * @param $end
     * @param $file
     * @return string|null
     */
    public function getContent($start, $end, $file): ?string
    {
        return shell_exec("cat {$file} | awk '/{$start}/{f=1;next} /{$end}/{f=0} f'");
    }

    /**
     * Get author name given changelog file.
     *
     * @param $file
     * @return string
     */
    public function getAuthor($file): string
    {
        return exec("grep 'author:' {$file} | sed 's/^.*: //' ");
    }

    public function publishFileContent($file): void
    {
        $changelogFile = config('app.structure.main');

        $author = $this->getAuthor($file);

        foreach ($this->categories as $category) {
            $categoryText = '### '.$category;
            $lineNumber = $this->search($categoryText, $changelogFile);

            $content = $this->getContent($categoryText, '###', $file);

            if ($content) {
                // add author each line to content.
                $lines = explode("\n", $content);

                foreach($lines as $lineNr => $line){
                    if ($line) {
                        $trimmed = trim($line, "\r");

                        if ($trimmed !== '' && !preg_match('/^\s/', $trimmed) && !str_starts_with($trimmed, '- ')) {
                            $trimmed = '- ' . $trimmed;
                        }

                        $lines[$lineNr] = $trimmed . " (" . $author . ")";
                    }
                }

                $lines = array_filter($lines, fn ($line) => trim($line) !== '');
                $newContent = implode("\n", $lines);
                $newContent = str_replace("'", "\u{2019}", $newContent);

                $this->writeAfterLine($newContent, $lineNumber, $changelogFile);
            }
        }
    }

    public function removeEmptyCategories(): void
    {
        $changelogFile = config('app.structure.main');
        $lines = file($changelogFile, FILE_IGNORE_NEW_LINES);
        $result = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            if (preg_match('/^### (Added|Changed|Deprecated|Fixed|Removed|Security)\s*$/', $lines[$i])) {
                $nextContentIndex = $i + 1;

                while ($nextContentIndex < $count && trim($lines[$nextContentIndex]) === '') {
                    $nextContentIndex++;
                }

                if ($nextContentIndex < $count && preg_match('/^##+ /', $lines[$nextContentIndex])) {
                    $i = $nextContentIndex;
                    continue;
                }

                if ($nextContentIndex >= $count) {
                    $i = $nextContentIndex;
                    continue;
                }
            }

            $result[] = $lines[$i];
            $i++;
        }

        file_put_contents($changelogFile, implode("\n", $result) . "\n");
    }
}
