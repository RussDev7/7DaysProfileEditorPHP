<?php

class TtpParser
{
    private $file_path;
    private $file_handle;
    private $file_content;

    public function __construct(string $file_path)
    {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            throw new Exception("ファイルが存在しないか、読み込めません。");
        }
        $this->file_path = $file_path;
        $this->file_handle = fopen($this->file_path, 'rb');
        $this->file_content = file_get_contents($this->file_path);
        if ($this->file_handle === false || $this->file_content === false) {
            throw new Exception("ファイルの読み込みに失敗しました。");
        }
    }

    public function __destruct()
    {
        if ($this->file_handle) {
            fclose($this->file_handle);
        }
    }

    public function parse(): array
    {
        return [
            'player_info' => $this->extractPlayerInfo(),
            'statuses' => $this->extractStatuses(),
            'quests_and_pois' => $this->extractQuestsAndPois(),
            // 'skills_and_perks' は statuses に統合されるため、個別呼び出しは不要になる
        ];
    }

    private function extractPlayerInfo(): array
    {
        $info = ['name' => '自動検出失敗']; // デフォルト値

        // ▼▼▼ 変更箇所：新しいプレイヤー名検出アルゴリズム ▼▼▼

        // 1. アンカーとなる2回目の「ÿÿ」（FF FF）を探す
        $anchor = "\xFF\xFF";
        $first_pos = strpos($this->file_content, $anchor);

        if ($first_pos !== false) {
            // 1回目の出現位置の直後から、2回目を探す
            $second_pos = strpos($this->file_content, $anchor, $first_pos + strlen($anchor));

            if ($second_pos !== false) {
                // 2. アンカーの直後から検索を開始
                $search_offset = $second_pos + strlen($anchor);
                
                // 3. 検索開始位置以降の文字列で、最初に出現する3文字以上の印刷可能文字（ASCIIの空白～チルダ）をプレイヤー名と見なす
                $subject = substr($this->file_content, $search_offset);
                if (preg_match('/([ -~]{3,})/', $subject, $matches)) {
                    $info['name'] = trim($matches[1]);
                }
            }
        }
        // ▲▲▲ 変更箇所 ▲▲▲

        // EOS IDの抽出 (変更なし)
        if (preg_match('/(EOS\s[0-9a-f]{32})/', $this->file_content, $matches)) {
            $info['eos_id'] = $matches[1];
        }

        return $info;
    }

    private function extractStatuses(): array
    {
        $results = [];

        // ▼▼▼ 変更箇所：正規表現による動的なキーワード抽出 ▼▼▼

        // 1. トレーダーと主要クエストを検索 (従来通り)
        $targets = ['trader_rekt', 'trader_jen', 'trader_bob', 'intro_buried_supplies', 'quest_tier1complete'];
        foreach ($targets as $target) {
            $offset = strpos($this->file_content, $target);
            if ($offset !== false) {
                 $results[] = $this->readStatusBytes($target, $offset);
            }
        }

        // 2. 正規表現でスキル、パーク、能力値などを全て検索
        $pattern = '/((?:skill|att|crafting|perk)[a-zA-Z0-9]+|skillpoint)/';
        // PREG_OFFSET_CAPTURE を使い、キーワードの出現位置(オフセット)も取得する
        preg_match_all($pattern, $this->file_content, $matches, PREG_OFFSET_CAPTURE);

        if (!empty($matches[0])) {
            $found_keywords = [];
            foreach ($matches[0] as $match) {
                $keyword = $match[0];
                $offset = $match[1];
                
                // 同じキーワードが複数回見つかることがあるため、重複を避ける
                if (!isset($found_keywords[$keyword])) {
                    $results[] = $this->readStatusBytes($keyword, $offset);
                    $found_keywords[$keyword] = true;
                }
            }
        }
        // ▲▲▲ 変更箇所 ▲▲▲

        return $results;
    }

    /**
     * 指定されたオフセットからステータス候補のバイトを読み込むヘルパー関数
     * @param string $name キーワード名
     * @param int $offset キーワードの開始オフセット
     * @return array|null
     */
    private function readStatusBytes(string $name, int $offset): ?array
    {
        // キーワードの4バイト手前を読む
        $status_offset = max(0, $offset - 4);
        fseek($this->file_handle, $status_offset);
        $bytes = fread($this->file_handle, 4);
        
        if ($bytes !== false) {
            return [
                'name' => $name,
                'potential_status_hex' => strtoupper(str_pad(bin2hex($bytes), 8, '0', STR_PAD_LEFT))
            ];
        }
        return null;
    }

    private function extractQuestsAndPois(): array
    {
        // (このメソッドは変更ありません)
        preg_match_all('/(quest_[a-zA-Z0-9_]+|tier[1-9]_[a-zA-Z0-9_]+|POIName\s[^\x00-\x1F]+)/', $this->file_content, $matches);
        $quests = array_unique($matches[0]);
        // 結果が多くなりすぎるため、表示を50件に制限
        return array_slice($quests, 0, 50);
    }
}