# 7 Days to Die - Web版プレイヤープロフィール解析
これは、7 Days to Dieのプレイヤープロファイルファイル（.ttp）を解析・表示するためのWebベースのツールです。PCにソフトウェアをインストールすることなく、Webブラウザ経由でセーブデータの内容を簡単に確認できます。
**v2.2, v2.3**のセーブデータに最適化されています。
このツールは、Karlovsky120/7DaysProfileEditorに大きくインスパイアされ、より手軽に利用できるモダンなサーバーサイド版として開発されました。

## 主な機能
- 🖥️ Webベース: クライアントへのインストールは不要です。Webサーバーさえあれば、どのデバイスからでも利用できます。
- 📤 簡単なファイルアップロード: シンプルなインターフェースから、.ttpファイルを直接アップロードして解析を開始できます。
- 🤖 動的なプレイヤー名検出: ファイルのバイナリ構造を分析し、プレイヤー名を自動で正確に検出します。
- 📊 詳細なデータ表示: クエストの進捗、スキル、パーク、能力値などの内部データを一覧で表示します。
- HEX値の可視化: スキルレベルやパークの取得状況など、ステータスに関連する可能性のある値を16進数で抽出し、詳細な分析をサポートします。

## 動作環境
- PHP 7.4 以上
- Webサーバー (Apache, Nginxなど)

## 使い方
このリポジトリのファイルをダウンロードし、お使いのWebサーバーの公開ディレクトリに配置します。
1. Webブラウザで index.php にアクセスします。
2. 7 Days to Dieのセーブデータフォルダにあるプレイヤープロファイルファイル（例: EOS_....ttp）を選択し、「解析する」ボタンをクリックします。
3. 解析結果が画面に表示されます。

### セーブデータの場所 (デフォルト):
C:\Users\[あなたのユーザー名]\AppData\Roaming\7DaysToDie\Saves\[ワールド名]\[プレイヤーID]\

## 解析の仕組み
このツールは、.ttpファイルをバイナリデータとして読み込み、以下のロジックで情報を抽出しています。
プレイヤー名: ファイルヘッダの構造的特徴（2回目のFF FFバイトシーケンス）をアンカーとし、その後に出現する最初の有効な文字列をプレイヤー名として特定します。
スキル・パーク: skill..., att..., perk... などの命名規則を持つキーワードを正規表現で網羅的に検索し、そのキーワードの直前のバイナリデータをステータス候補（16進数）として読み込みます。

## 今後の予定 (Roadmap)
- 編集機能: 値（スキルレベルなど）の変更と、有効な.ttpファイルとしての書き戻し機能の実装。
- データマッピング: perkdeadeye のような内部名を「デッドアイ」のような日本語の表示名に変換する機能。
- 16進数データのデコード: ステータス候補の16進数値を、実際のレベルやポイント数（整数）に変換して表示する。
- 他ファイルへの対応: プレイヤープロファイル以外のセーブデータ（マップデータなど）の解析。

### ライセンス
このプロジェクトは MITライセンス の下で公開されています。

### 謝辞
このツールは、Karlovsky120/7DaysProfileEditor(https://github.com/Karlovsky120/7DaysProfileEditor/) の先駆的な功績に大きくインスパイアされています。素晴らしい着想とオリジナルの実装に心から感謝します。

# 7 Days to Die - Web Profile Analyzer
This is a web-based tool for parsing and displaying 7 Days to Die player profile (.ttp) files. It allows you to easily inspect your save data via a web browser without installing any client-side software.
This tool is optimized for Alpha 21 (v2.2 & v2.3) save files.
It was heavily inspired by Karlovsky120/7DaysProfileEditor, and was developed as a modern, server-side alternative for easy access.

## Features
- 🖥️ Web-Based: No client-side installation required. Runs on any standard web server.
- 📤 Easy File Upload: A simple interface to upload and analyze your .ttp file.
- 🤖 Dynamic Player Name Detection: Automatically and accurately detects the player name by analyzing the file's binary structure.
- 📊 Detailed Data View: Displays internal data such as quest progress, skills, perks, and attributes.
- HEX Status Viewer: Extracts and displays potential status values (e.g., skill levels, perk points) in hexadecimal to aid in detailed analysis.

## Requirements
- PHP 7.4 or higher
- A web server (e.g., Apache, Nginx)

## Usage
- Download the files from this repository and place them in your web server's public directory.
- Access index.php in your web browser.
- Select your player profile file (e.g., EOS_....ttp) from your 7 Days to Die saves folder and click "Analyze".
- The parsed results will be displayed on the screen.

### Default Save Location:
C:\Users\[Your Username]\AppData\Roaming\7DaysToDie\Saves\[World Name]\[Player ID]\

## How It Works
This tool reads the .ttp file as binary data and extracts information using the following logic:
Player Name Detection: The player's name is identified using a structural anchor within the binary file (specifically, the second occurrence of the FF FF byte sequence), rather than relying on inconsistent neighboring strings.
Skill & Perk Parsing: A regular expression is used to comprehensively find all keywords matching known patterns (skill..., att..., perk..., etc.). The parser then reads the adjacent bytes for each keyword to display its potential status value.

### Roadmap
- Editing Feature: Implement the ability to modify values and write them back to a valid .ttp file.
- Data Mapping: Convert internal names like perkdeadeye to user-friendly display names like "Deadeye".
- Hex Value Decoding: Translate hexadecimal status values (e.g., 01000000) into integers (1).
- Support for Other Files: Expand parsing capabilities to include other save files, such as map data.

### License
This project is licensed under the MIT License.

### Acknowledgments
This tool was heavily inspired by the pioneering work on the Karlovsky120/7DaysProfileEditor(https://github.com/Karlovsky120/7DaysProfileEditor/). A big thank you for the original concept and implementation.