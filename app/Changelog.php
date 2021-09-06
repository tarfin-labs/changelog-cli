<?php


namespace App;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Changelog
{
    const ADDED = 'New feature';
    const FIXED = 'Bug fix';
    const CHANGED = 'Feature change';
    const DEPRECATED = 'New deprecation';
    const REMOVED = 'Feature removal';
    const SECURITY = 'Security fix';

    public $menuName = 'Changelog category';

    public $menuItems = [
        'New feature',
        'Bug fix',
        'Feature change',
        'New deprecation',
        'Feature removal',
        'Security fix',
    ];

    public $categories = [
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

    public function appendCategories()
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
    public function getContent($start, $end, $file)
    {
        return shell_exec("cat {$file} | awk '/{$start}/{f=1;next} /{$end}/{f=0} f'");
    }

    /**
     * Get author name given changelog file.
     *
     * @param $file
     * @return string
     */
    public function getAuthor($file)
    {
        return exec("grep 'author:' {$file} | sed 's/^.*: //' ");
    }

    public function publishFileContent($file)
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
                        $lines[$lineNr] = trim($line,"\r") . " (" . $author . ")";
                    }
                }

                $newContent = implode("\n", $lines);

                $this->writeAfterLine($newContent, $lineNumber, $changelogFile);
            }
        }
    }
}
