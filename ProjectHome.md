PHP Class for video information extraction. Some of the features are:
  * Subtitles extraction, and formatting into SUB and SRT format.
  * Thumbnail and title extraction.

Using the class is easy:
```
<?php 
  $x = new vbox7("http://vbox7.com/play:87ae93cf", VBOX7_SUBTITLES);
  echo $x->subtitles;
?>
```