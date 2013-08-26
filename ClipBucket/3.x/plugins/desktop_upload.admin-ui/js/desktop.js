$(document).ready(function()
{
    $("#DesktopOn").click(function()
    {
        $("#AllSettings").fadeIn("slow");
        $("#AudioSettings").fadeIn("slow");
        $("#ImageSettings").fadeIn("slow");
        $("#VideoSettings").fadeIn("slow");
        $("#YouTubeSettings").fadeIn("slow");
    });

    $("#DesktopOff").click(function()
    {
        $("#AllSettings").fadeOut("slow");
        $("#AudioSettings").fadeOut("slow");
        $("#ImageSettings").fadeOut("slow");
        $("#VideoSettings").fadeOut("slow");
        $("#YouTubeSettings").fadeOut("slow");
    });

    $("#moderateGlobal").click(function()
    {
        $("#AdminApproveAudio").fadeIn("slow");
        $("#AdminApproveImage").fadeIn("slow");
        $("#AdminApproveVideo").fadeIn("slow");
    });

    $("#moderateUser").click(function()
    {
        $("#AdminApproveAudio").fadeOut("slow");
        $("#AdminApproveImage").fadeOut("slow");
        $("#AdminApproveVideo").fadeOut("slow");
    });

    $("#ImageUploadOn").click(function()
    {
        $("#ImageSpecific").fadeIn("slow");
    });

    $("#ImageUploadOff").click(function()
    {
        $("#ImageSpecific").fadeOut("slow");
    });

    $("#VideoUploadOn").click(function()
    {
        $("#VideoSpecific").fadeIn("slow");
    });

    $("#VideoUploadOff").click(function()
    {
        $("#VideoSpecific").fadeOut("slow");
    });

    $("#uploadQuota-global").click(function()
    {
        $("#AudioMaxFileSize").fadeIn("slow");
        $("#ImageMaxFileSize").fadeIn("slow");
        $("#VideoMaxFileSize").fadeIn("slow");
    });

    $("#uploadQuota-user").click(function()
    {
        $("#AudioMaxFileSize").fadeOut("slow");
        $("#ImageMaxFileSize").fadeOut("slow");
        $("#VideoMaxFileSize").fadeOut("slow");
    });

    $("#NoAudioUploads").click(function()
    {
        $("#AudioMaxFileSize").fadeOut("slow");
        $("#AudioRequirePic").fadeOut("slow");
    });

    $("#YesAudioUploads").click(function()
    {
        if ($("#uploadQuota-global").is(":checked"))
        {
            $("#AudioMaxFileSize").fadeIn("slow");
        }
        $("#AudioRequirePic").fadeIn("slow");
    });

    $("#NoImageUploads").click(function()
    {
        $("#ImageAllowExtensions").fadeOut("slow");
        $("#ImageMaxFileSize").fadeOut("slow");
    });

    $("#YesImageUploads").click(function()
    {
        $("#ImageAllowExtensions").fadeIn("slow");
        if ($("#uploadQuota-global").is(":checked"))
        {
            $("#ImageMaxFileSize").fadeIn("slow");
        }
    });

    $("#NoVideoUploads").click(function()
    {
        $("#VideoMaxFileSize").fadeOut("slow");
        $("#allowed_video_format").fadeOut("slow");
        $("#VideoAllowExtensions").fadeOut("slow");
        $("#VideoResize").fadeOut("slow");
        $("#ResizeDim").fadeOut("slow");
        $("#FLV_Directive").fadeOut("slow");
        $("#MP4_Directive").fadeOut("slow");
    });

    $("#YesVideoUploads").click(function()
    {
        if ($("#uploadQuota-global").is(":checked"))
        {
            $("#VideoMaxFileSize").fadeIn("slow");
        }
        $("#allowed_video_format").fadeIn("slow");
        $("#VideoAllowExtensions").fadeIn("slow");
        $("#VideoResize").fadeIn("slow");
        if ($("#VideoResizeType").val() != 0)
        {
            $("#ResizeDim").fadeIn("slow");
        }
        $("#FLV_Directive").fadeIn("slow");
        $("#MP4_Directive").fadeIn("slow");
    });

    $("#VideoResizeType").change(function()
    {
        if($(this).val() != 0)
        {
            $("#ResizeDim").fadeIn("slow");
        }
        else
        {
            $("#ResizeDim").fadeOut("slow");
        }
    });

    $("#YTResizeEmbedOn").click(function()
    {
        $("#YouTubeEmbedResizeDim").fadeIn("slow");
    });

    $("#YTResizeEmbedOff").click(function()
    {
        $("#YouTubeEmbedResizeDim").fadeOut("slow");
    });

    $("#YTEmbedOn").click(function()
    {
        $("#YouTubeEmbedSpecific").fadeIn("slow");
    });

    $("#YTEmbedOff").click(function()
    {
        $("#YouTubeEmbedSpecific").fadeOut("slow");
    });

});