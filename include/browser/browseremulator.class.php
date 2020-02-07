<?php
/***************************************************************************

Browser Emulating file functions v2.0
(c) Kai Blankenhorn
www.bitfolge.de/en
kaib@bitfolge.de


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

****************************************************************************


Changelog:

v2.0	03-09-03
    added a wrapper class; this has the advantage that you no longer need
        to specify a lot of parameters, just call the methods to set
        each option
    added option to use a special port number, may be given by setPort or
        as part of the URL (e.g. server.com:80)
    added getLastResponseHeaders()

v1.5
    added Basic HTTP user authorization
    minor optimizations

v1.0
    initial release



***************************************************************************/
/**
 * BrowserEmulator class. Provides methods for opening urls and emulating
 * a web browser request.
 **/
     class BrowserEmulator
     {
         public $headerLines = array();
         public $postData = array();
         public $authUser = "";
         public $authPass = "";
         public $port;
         public $lastResponse = array();

         public function BrowserEmulator()
         {
             $this->resetHeaderLines();
             $this->resetPort();
         }

         /**
         * Adds a single header field to the HTTP request header. The resulting header
         * line will have the format
         * $name: $value\n
         **/
         public function addHeaderLine($name, $value)
         {
             $this->headerLines[$name] = $value;
         }

         /**
         * Deletes all custom header lines. This will not remove the User-Agent header field,
         * which is necessary for correct operation.
         **/
         public function resetHeaderLines()
         {
             $this->headerLines = array();

             /*******************************************************************************/
             /**************    YOU MAX SET THE USER AGENT STRING HERE    *******************/
             /*                                                                             */
             /* default is "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",            */
             /* which means Internet Explorer 6.0 on WinXP                                  */

             $this->headerLines["User-Agent"] =
           "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";

             /*******************************************************************************/
         }

         /**
         * Add a post parameter. Post parameters are sent in the body of an HTTP POST request.
         **/
         public function addPostData($name, $value)
         {
             $this->postData[$name] = $value;
         }

         /**
         * Deletes all custom post parameters.
         **/
         public function resetPostData()
         {
             $this->postData = array();
         }

         /**
         * Sets an auth user and password to use for the request.
         * Set both as empty strings to disable authentication.
         **/
         public function setAuth($user, $pass)
         {
             $this->authUser = $user;
             $this->authPass = $pass;
         }

         /**
         * Selects a custom port to use for the request.
         **/
         public function setPort($portNumber)
         {
             $this->port = $portNumber;
         }

         /**
         * Resets the port used for request to the HTTP default (80).
         **/
         public function resetPort()
         {
             $this->port = 80;
         }

         /**
         * Make an fopen call to $url with the parameters set by previous member
         * method calls. Send all set headers, post data and user authentication data.
         * Returns a file handle on success, or false on failure.
         **/
         public function fopen($url)
         {
             $debug = false;

             $this->lastResponse = array();

             preg_match(
          "~([a-z]*://)?([^:^/]*)(:([0-9]{1,5}))?(/.*)?~i",
          $url,
          $matches
      );
             if ($debug) {
                 var_dump($matches);
             }
             $protocol = $matches[1];
             $server = $matches[2];
             $port = $matches[4];
             $path = $matches[5];
             if ($port != "") {
                 $this->setPort($port);
             }
             if ($path == "") {
                 $path = "/";
             }
             $socket = false;
             $socket = fsockopen($server, $this->port);
             if ($socket) {
                 $this->headerLines["Host"] = $server;

                 if ($this->authUser != "" and $this->authPass != "") {
                     $headers["Authorization"] =
             "Basic ".base64_encode($this->authUser.":".$this->
                         authPass);
                 }

                 if (count($this->postData) == 0) {
                     $request = "GET $path HTTP/1.0\r\n";
                 } else {
                     $request = "POST $path HTTP/1.0\r\n";
                 }

                 if ($debug) {
                     echo $request;
                 }
                 fputs($socket, $request);

                 if (count($this->postData) > 0) {
                     $PostStringArray = array();
                     foreach ($this->postData as $key => $value) {
                         $PostStringArray[] = "$key=$value";
                     }
                     $PostString = join("&", $PostStringArray);
                     $this->headerLines["Content-Length"] =
             strlen($PostString);
                 }

                 foreach ($this->headerLines as $key => $value) {
                     if ($debug) {
                         echo "$key: $value\n";
                     }
                     fputs($socket, "$key: $value\r\n");
                 }
                 if ($debug) {
                     echo "\n";
                 }
                 fputs($socket, "\r\n");
                 if (count($this->postData) > 0) {
                     if ($debug) {
                         echo "$PostString";
                     }
                     fputs($socket, $PostString."\r\n");
                 }
             }
             if ($debug) {
                 echo "\n";
             }
             if ($socket) {
                 $line = fgets($socket, 1000);
                 if ($debug) {
                     echo $line;
                 }
                 $this->lastResponse[] = $line;
                 $status = substr($line, 9, 3);
                 while (trim($line = fgets($socket, 1000)) != "") {
                     if ($debug) {
                         echo "$line";
                     }
                     $this->lastResponse[] = $line;
                     if ($status == "401" and strpos($line, "WWW-Authenticate: Basic realm=\"")  === 0) {
                         fclose($socket);
                         return false;
                     }
                 }
             }
             return $socket;
         }

         /**
         * Make an file call to $url with the parameters set by previous member
         * method calls. Send all set headers, post data and user authentication data.
         * Returns the requested file as an array on success, or false on failure.
         **/
         public function file($url)
         {
             $file = array();
             $socket = $this->fopen($url);
             if ($socket) {
                 $file = array();
                 while (!feof($socket)) {
                     $file[] = fgets($socket, 10000);
                 }
             } else {
                 return false;
             }
             return $file;
         }

         public function getLastResponseHeaders()
         {
             return $this->lastResponse;
         }
     }



// example code
/*
$be = new BrowserEmulator();
//$be->addHeaderLine("Referer", "http://previous.server.com/");
//$be->addHeaderLine("Accept-Encoding", "x-compress; x-zip");
//$be->addPostData("Submit", "OK");
//$be->addPostData("item", "42");
//$be->setAuth("admin", "secretpass");
// also possible:
// $be->setPort(10080);

$file = $be->fopen("http://us.imdb.com/Title?0209144");
$response = $be->getLastResponseHeaders();

while ($line = fgets($file, 1024)) {
    // do something with the file
    echo $line;
}
fclose($file);

*/
