<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否有文件上传
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        // 检查文件类型
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileExtension === 'txt') {
            // 读取上传的 TXT 文件
            $txtContent = file_get_contents($file);
            $txtLines = explode("\n", $txtContent);

            // 构建 M3U 格式的内容
            $m3uContent = "#EXTM3U\n";
            foreach ($txtLines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $parts = explode(',', $line);
                    if (count($parts) === 2) {
                        $channelName = trim($parts[0]);
                        $playUrl = trim($parts[1]);
                        $m3uContent .= "#EXTINF:-1,$channelName\n";
                        $m3uContent .= $playUrl . "\n";
                    }
                }
            }

            // 清除输出缓冲区
            ob_clean();

            // 设置响应头，提示下载文件
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '.m3u"');

            // 输出 M3U 内容到浏览器
            echo $m3uContent;
            exit(); // 终止脚本执行，确保不会输出额外的内容
        } elseif ($fileExtension === 'm3u') {
            // 读取上传的 M3U 文件
            $m3uContent = file_get_contents($file);

            // 提取频道名称和播放地址
            $txtContent = '';
            preg_match_all('/#EXTINF:-1\s?,\s?(.*)\n(.*)\n/', $m3uContent, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $channelName = trim($match[1]);
                $playUrl = trim($match[2]);
                $txtContent .= $channelName . ',' . $playUrl . "\n";
            }

            // 清除输出缓冲区
            ob_clean();

            // 设置响应头，提示下载文件
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '.txt"');

            // 输出 TXT 内容到浏览器
            echo $txtContent;
            exit(); // 终止脚本执行，确保不会输出额外的内容
        } else {
            echo "不支持的文件类型。";
        }
    } else {
        echo "文件上传失败。";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 检查是否提供了文件的 Web 地址
    if (isset($_GET['url'])) {
        $url = $_GET['url'];

        // 验证 URL 是否合法
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            echo "提供的 Web 地址无效。";
            exit();
        }

        // 读取文件内容
        $fileContent = @file_get_contents($url);

        // 检查文件是否成功读取
        if ($fileContent === false) {
            echo "无法读取文件内容。";
            exit();
        }

        // 检查文件格式并进行相应的转换
        if (strpos($url, '.m3u') !== false) {
            // M3U 格式转 TXT 格式
            $txtContent = '';
            preg_match_all('/#EXTINF:-1\s?,\s?(.*)\n(.*)\n/', $fileContent, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $channelName = trim($match[1]);
                $playUrl = trim($match[2]);
                $txtContent .= $channelName . ',' . $playUrl . "\n";
            }

            // 输出 TXT 内容到浏览器
            header('Content-Type: text/plain');
            echo $txtContent;
        } else {
            // TXT 格式转 M3U 格式
            $m3uContent = "#EXTM3U\n";
            $lines = explode("\n", $fileContent);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $parts = explode(',', $line);
                    if (count($parts) === 2) {
                        $channelName = trim($parts[0]);
                        $playUrl = trim($parts[1]);
                        $m3uContent .= "#EXTINF:-1, $channelName\n$playUrl\n";
                    }
                }
            }

            // 输出 M3U 内容到浏览器
            header('Content-Type: application/x-mpegURL');
            echo $m3uContent;
        }

        exit(); // 终止脚本执行，确保不会输出额外的内容
    } else {
        echo "未提供文件的 Web 地址。";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>格式转换</title>
</head>
<body>
    <h2>本地M3U格式和TXT格式互转</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" value="转换">
    </form>
    <h2>在线M3U格式和TXT格式互转</h2>
    <form method="get" action="">
        <input type="text" name="url" placeholder="输入文件的 Web 地址">
        <input type="submit" value="转换">
    </form>
    
    <br>
    <hr>
    <h3>功能介绍：</h3>
    <p>
        该程序可以将 M3U 格式和 TXT 格式的直播源相互转换。<br>
        - 本地 M3U 格式和 TXT 格式互转：请选择一个 M3U或txt 文件并上传，系统将自动将其转换为对应的格式文件进行下载。<br>
        - 在线直播源格式转换：在文本框中输入包含直播源的 Web 地址，点击转换按钮，系统将自动将其转换为对应的 M3U 格式或txt文件。这可以这样调用 .php?url=https://www.xxx.com/hd.txt
    </p>
</body>
</html>
