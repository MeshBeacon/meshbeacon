//import ApexCharts from 'apexcharts'

// Configure jQuery so every AJAX request:
//  - sends Accept: application/json  → Laravel returns 401 JSON (not a login redirect)
//  - sends the CSRF token for mutating requests
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        Accept: "application/json",
    },
});

// Global handler: redirect to /login on any 401 (session expired / not authenticated)
$(document).ajaxError(function (event, jqXHR) {
    if (jqXHR.status === 401) {
        window.location.href = "/login";
    }
});

$(document).ready(function () {
    // Initialize the DataTable
    var table = $("#ducks-table").DataTable({
        dom: "t",
        order: [[1, "desc"]],
        processing: true,
        language: {
            loadingRecords:
                '<div class="dt-empty">Loading data, please wait...</div>',
            processing: '<div class="dt-empty">Processing...</div>',
        },
        ajax: {
            url: "/dashboard/json",
            dataSrc: "data",
        },
        drawCallback: function () {
            $("#ducks-table tbody .msg-text").each(function () {
                var el = this;
                var $btn = $(el).siblings(".msg-toggle");
                // scrollHeight > clientHeight means text is clamped
                if (el.scrollHeight <= el.clientHeight) {
                    $btn.hide();
                } else {
                    $btn.show();
                }
            });
        },
        columns: [
            {
                data: "duck_id",
                defaultContent: "",
                className:
                    "relative border-t border-transparent py-4 pl-4 pr-3 text-sm sm:pl-6",
                render: function (data, type, row, meta) {
                    return (
                        '<div class="font-medium text-white">' +
                        data +
                        '</div><div class="absolute -top-px left-6 right-0 h-px bg-white/10"></div>'
                    );
                },
            },
            {
                data: "created_at",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
                render: function (data, type, row) {
                    if (type === "sort" || type === "type") return data;
                    return new Date(data).toLocaleString(navigator.language, {
                        "12hour": false,
                    });
                },
            },
            {
                data: "topic",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "message_id",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "path",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
                render: function (data) {
                    if (!data) return '<span class="italic text-gray-600">&mdash;</span>';
                    var hops = data.split(',');
                    var parts = hops.map(function (hop, i) {
                        return (i > 0 ? '<span class="text-gray-500 mx-0.5">&#8594;</span>' : '') +
                            '<span class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-gray-200">' +
                            escapeHtml(hop.trim()) + '</span>';
                    });
                    return '<div class="flex flex-wrap items-center gap-1">' + parts.join('') + '</div>';
                },
            },
            {
                data: "display_text",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell",
                render: function (data, type, row) {
                    // RREP rows have no message payload — show origin → destination instead
                    if (row.topic === "rrep") {
                        var origin = row.origin || "?";
                        var dest   = row.destination || "?";
                        return (
                            '<div style="display:flex;align-items:center;gap:0.375rem;">' +
                            '<span style="flex-shrink:0;" class="inline-flex items-center justify-center rounded bg-purple-700 px-1.5 py-0.5 text-xs font-bold text-white">RREP</span>' +
                            '<span class="font-mono text-xs text-gray-300">' +
                            escapeHtml(origin) +
                            '</span>' +
                            '<span class="text-gray-500">&#8594;</span>' +
                            '<span class="font-mono text-xs text-gray-300">' +
                            escapeHtml(dest) +
                            '</span>' +
                            '</div>'
                        );
                    }
                    var payload = row.payload || "";
                    var rawText = data || payload || "";
                    var isSosDev =
                        /^SOS/i.test(payload) && /SRC:DEVICE/i.test(payload);
                    var isSosMob =
                        /^SOS/i.test(payload) && !/SRC:DEVICE/i.test(payload);
                    var isMsg = /^MSG\b/i.test(payload);
                    var tag =
                        '<span style="width:3.5rem;flex-shrink:0;display:inline-block;"></span>';
                    if (isSosDev)
                        tag =
                            '<span style="flex-shrink:0;" class="inline-flex items-center justify-center rounded bg-red-600 px-1.5 py-0.5 text-xs font-bold text-white whitespace-nowrap">SOS HW</span>';
                    else if (isSosMob)
                        tag =
                            '<span style="flex-shrink:0;width:3.5rem;" class="inline-flex items-center justify-center rounded bg-orange-500 px-1.5 py-0.5 text-xs font-bold text-white">SOS</span>';
                    else if (isMsg)
                        tag =
                            '<span style="flex-shrink:0;width:3.5rem;" class="inline-flex items-center justify-center rounded bg-indigo-600 px-1.5 py-0.5 text-xs font-bold text-white">MSG</span>';
                    var TRUNCATED_STYLE =
                        "display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;word-break:break-all;white-space:normal;";
                    var btn =
                        '<button class="msg-toggle" style="display:block;margin-left:auto;color:#818cf8;font-size:0.7rem;font-weight:500;cursor:pointer;background:none;border:none;padding:0;white-space:nowrap;text-align:right;" onmouseover="this.style.color=\'#a5b4fc\'" onmouseout="this.style.color=\'#818cf8\'">Show more</button>';
                    var inner =
                        '<div style="flex:1;min-width:0;"><span class="msg-text" style="' +
                        TRUNCATED_STYLE +
                        '">' +
                        escapeHtml(rawText) +
                        "</span>" +
                        btn +
                        "</div>";
                    return (
                        '<div style="display:flex;align-items:flex-start;gap:0.5rem;min-width:0;">' +
                        tag +
                        inner +
                        "</div>"
                    );
                },
            },
            {
                data: "hops",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "duck_type",
                defaultContent: "",
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "urgency_label",
                defaultContent: "",
                orderable: false,
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm lg:table-cell",
                render: function (data, type, row) {
                    if (data == null)
                        return '<span class="text-gray-600">&mdash;</span>';
                    var u =
                        urgencyMap[String(row.urgency_value)] ||
                        urgencyMap["0"];
                    return (
                        '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ' +
                        u.cls +
                        '">' +
                        escapeHtml(data) +
                        "</span>"
                    );
                },
            },
            {
                data: "map_embed_url",
                defaultContent: null,
                orderable: false,
                className:
                    "hidden border-t border-white/10 px-3 py-3.5 text-sm lg:table-cell",
                render: function (data, type, row) {
                    if (!data)
                        return '<span class="text-gray-600">&mdash;</span>';
                    return (
                        '<button class="dt-map-btn inline-flex items-center gap-1 rounded-md bg-white/10 px-2 py-1 text-xs font-semibold text-white hover:bg-white/20" data-embed="' +
                        escapeHtml(data) +
                        '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M8 1a5 5 0 0 1 5 5c0 2.813-2.45 5.714-4.168 7.603a1.145 1.145 0 0 1-1.664 0C5.45 11.714 3 8.813 3 6a5 5 0 0 1 5-5Zm0 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" /></svg>Map</button>'
                    );
                },
            },
        ],
    });

    $("#ducks-table tbody").on("click", ".msg-toggle", function () {
        var $btn = $(this);
        var $text = $btn.siblings(".msg-text");
        var expanded = $btn.data("expanded");
        if (expanded) {
            $text.css({
                display: "-webkit-box",
                "-webkit-line-clamp": "1",
                "-webkit-box-orient": "vertical",
                overflow: "hidden",
            });
            $btn.text("Show more");
            $btn.data("expanded", false);
        } else {
            $text.css({
                display: "block",
                "-webkit-line-clamp": "unset",
                overflow: "visible",
            });
            $btn.text("Show less");
            $btn.data("expanded", true);
        }
    });

    $("#ducks-table tbody").on("click", ".dt-map-btn", function () {
        var tr = $(this).closest("tr");
        var row = table.row(tr);
        var url = $(this).data("embed");
        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass("dt-map-shown");
        } else {
            row.child(
                '<div class="p-3 bg-gray-900">' +
                    '<iframe src="' +
                    url +
                    '" class="w-full h-64 rounded-md border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' +
                    "</div>",
            ).show();
            tr.addClass("dt-map-shown");
        }
    });

    $("#custom-filter").on("keydown", function () {
        table.ajax.reload();
    });

    // Link the custom input to the DataTables search functionality
    $("#custom-filter").on("keyup", function () {
        table.search(this.value).draw();
    });

    // rows drop down
    $("#table-select").val(table.page.len());
    // 3. Add a change event listener to your custom dropdown
    $("#table-select").on("change", function () {
        // Get the selected value
        var selectedValue = $(this).val();

        // Use the DataTables API to change the page length and redraw the table
        table.page.len(selectedValue).draw();
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    var urgencyMap = {
        0: {
            label: "Low",
            cls: "bg-green-500/20 text-green-400 ring-green-500/30",
        },
        1: {
            label: "Medium",
            cls: "bg-yellow-500/20 text-yellow-400 ring-yellow-500/30",
        },
        2: {
            label: "Critical",
            cls: "bg-red-500/20 text-red-400 ring-red-500/30",
        },
    };

    function urgencyBadge(raw) {
        var m = raw.match(/URGENCY:(\d)/i);
        var key = m ? m[1] : "0";
        var u = urgencyMap[key];
        if (!u) return "";
        return (
            '<span class="ml-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset ' +
            u.cls +
            '">' +
            u.label +
            "</span>"
        );
    }

    function sosDeviceTelemetryHtml(payload) {
        var battM = payload.match(/BATT:(\d+)/i);
        var altM = payload.match(/ALT:(-?\d+(?:\.\d+)?)/i);
        var spdM = payload.match(/SPD:(-?\d+(?:\.\d+)?)/i);
        var hdgM = payload.match(/HDG:(-?\d+(?:\.\d+)?)/i);
        if (!battM && !altM && !spdM && !hdgM) return "";
        var html =
            '<div style="margin-top:6px;display:flex;flex-direction:column;gap:5px;">';
        if (battM) {
            var b = parseInt(battM[1], 10);
            var battCls =
                b < 20
                    ? "background:rgba(127,29,29,0.7);color:#fca5a5"
                    : b < 50
                      ? "background:rgba(113,63,18,0.7);color:#fde68a"
                      : "background:rgba(20,83,45,0.7);color:#86efac";
            html +=
                '<div style="display:flex;align-items:flex-start;gap:5px;">' +
                '<span style="font-size:0.65rem;color:#6b7280;flex-shrink:0;min-width:2.5rem;padding-top:2px;">Device</span>' +
                '<div style="display:flex;flex-wrap:wrap;gap:4px;flex:1;">' +
                '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;' +
                battCls +
                '">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;flex-shrink:0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>' +
                b +
                "%" +
                "</span>" +
                "</div>" +
                "</div>";
        }
        if (altM || spdM || hdgM) {
            var gpsPills = "";
            if (altM) {
                gpsPills +=
                    '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(30,58,138,0.7);color:#93c5fd">' +
                    parseFloat(altM[1]).toFixed(1) +
                    " m alt" +
                    "</span>";
            }
            if (spdM) {
                gpsPills +=
                    '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(76,29,149,0.7);color:#c4b5fd">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;flex-shrink:0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>' +
                    parseFloat(spdM[1]).toFixed(1) +
                    " km/h" +
                    "</span>";
            }
            if (hdgM) {
                gpsPills +=
                    '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(12,74,110,0.7);color:#7dd3fc">' +
                    parseFloat(hdgM[1]).toFixed(1) +
                    "\u00b0" +
                    "</span>";
            }
            html +=
                '<div style="display:flex;align-items:flex-start;gap:5px;">' +
                '<span style="font-size:0.65rem;color:#6b7280;flex-shrink:0;min-width:2.5rem;padding-top:2px;">GPS</span>' +
                '<div style="display:flex;flex-wrap:wrap;gap:4px;flex:1;">' +
                gpsPills +
                "</div>" +
                "</div>";
        }
        html += "</div>";
        return html;
    }

    function urgencyRow(raw) {
        var m = raw.match(/URGENCY:(\d)/i);
        var key = m ? m[1] : "0";
        var u = urgencyMap[key];
        if (!u) return "";
        return (
            '<p class="mt-1 flex items-center gap-1.5 text-xs">' +
            '<span class="text-gray-500">Urgency:</span>' +
            '<span class="inline-flex items-center rounded-md px-1.5 py-0.5 font-medium ring-1 ring-inset ' +
            u.cls +
            '">' +
            u.label +
            "</span>" +
            "</p>"
        );
    }

    console.log("pulldata loads...");
    function pollData() {
        $.ajax({
            url: "/dashboard/timeline", // Server script to fetch data
            method: "GET",
            dataType: "json", // Expecting JSON data from the server
            success: function (data) {
                // Clear the table body to refresh with all data, or just append new rows
                console.log("timeline is processing...");

                let oldFeed = localStorage.getItem("feed");
                if (oldFeed == null) {
                    oldFeed = data;
                } else {
                    oldFeed = JSON.parse(oldFeed);
                }

                let template = [];
                $.each(data.data, function (index, value) {
                    // Iterate over the received data and append rows to the table
                    const date = new Date(value.created_at);
                    const time24h = date.toLocaleTimeString("en-GB", {
                        hourCycle: "h23",
                        hour: "2-digit",
                        minute: "2-digit",
                        second: "2-digit",
                    });

                    var payload = value.payload || "";
                    var isSos = /\bSOS\b/i.test(payload);
                    var isDevice = /\bSRC:DEVICE\b/i.test(payload);
                    var isMsg = /^MSG\b/i.test(payload);

                    var payloadHtml;
                    if (isSos && isDevice) {
                        payloadHtml =
                            '<span class="inline-flex items-center gap-1 font-semibold text-red-400">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>' +
                            "SOS \u2014 Hardware Button</span>";
                    } else if (isSos) {
                        payloadHtml =
                            '<span class="inline-flex items-center gap-1 font-semibold text-orange-400">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>' +
                            "SOS \u2014 Mobile App</span>";
                    } else if (isMsg) {
                        var textMatch = payload.match(/TEXT:([^,\n]+)/i);
                        var msgText = textMatch
                            ? escapeHtml(textMatch[1].trim())
                            : escapeHtml(payload);
                        payloadHtml =
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0 text-gray-400" style="flex-shrink:0;"><path fill-rule="evenodd" d="M1 8.74C1 9.99 1.99 11 3.21 11H4v1.306c0 .657.793 1.002 1.278.55l1.977-1.856H12.8c1.22 0 2.2-1.01 2.2-2.26V4.26C15 3.01 14.02 2 12.8 2H3.2C1.98 2 1 3.01 1 4.26v4.48Z" clip-rule="evenodd"/></svg>' +
                            '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;">' +
                            msgText +
                            "</span>";
                    } else {
                        payloadHtml = escapeHtml(payload);
                    }

                    var duckLink =
                        '<a href="/status" class="font-medium text-indigo-400 hover:text-indigo-300">' +
                        escapeHtml(value.duck_id) +
                        " &rarr;</a>";

                    var bodyHtml;
                    if (isMsg) {
                        bodyHtml =
                            '<p class="text-sm text-gray-300" style="display:flex;align-items:center;gap:0.25rem;min-width:0;overflow:hidden;">' +
                            payloadHtml +
                            "</p>" +
                            urgencyRow(payload) +
                            '<p class="mt-0.5 text-xs">' +
                            duckLink +
                            "</p>";
                    } else {
                        bodyHtml =
                            '<p class="text-sm text-gray-400 break-words">' +
                            payloadHtml +
                            "</p>" +
                            '<p class="mt-0.5 text-xs">' +
                            duckLink +
                            "</p>";
                    }

                    let templateData =
                        '<li><div class="relative pb-8"><div class="relative flex space-x-3"><div><img src="/images/logo.png" alt="Logo" class="size-10"></div><div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5"><div class="min-w-0 flex-1">' +
                        bodyHtml +
                        '</div><div class="whitespace-nowrap text-right text-sm text-gray-400 shrink-0"><time datetime="2020-09-22">' +
                        time24h +
                        "</time></div></div></div></div></li>";
                    template.push(templateData);
                });

                $("div.flow-root ul li").remove();
                $("div.flow-root ul").html(template.join(""));

                let feed = JSON.stringify(data.data);
                localStorage.setItem("feed", feed);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error(
                    "Error fetching data: " + textStatus,
                    errorThrown,
                );
            },
        });
    }

    // Poll the feed timeline every 5 seconds
    setInterval(pollData, 5000);

    // Initial call to load data when the page loads
    pollData();

    // Poll the summary stats cards (total, papaducks, mamaducks) as one request
    function pollStats() {
        $.ajax({
            url: "/dashboard/stats",
            method: "GET",
            dataType: "json",
            success: function (data) {
                $("#stat-messages-today").text(data.count);
                $("#stat-total").text(data.count);
                $("#stat-papaducks").text(data.papaducks);
                $("#stat-mamaducks").text(data.mamaducks);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error(
                    "Error fetching stats: " + textStatus,
                    errorThrown,
                );
            },
        });
    }

    setInterval(pollStats, 10000);
    pollStats();

    // Active Incidents panel — polls /dashboard/incidents every 30 s.
    // Shows the latest alert per duck from the past 24 h with the nearest
    // relay duck highlighted and a one-click GPS request button.
    var knownIncidentIds = null; // null = first load (don't alert on initial paint)

    function playIncidentAlert() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            // Two-tone alert: high beep then slightly lower beep
            [880, 660].forEach(function (freq, i) {
                var osc  = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = "sine";
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0, ctx.currentTime + i * 0.18);
                gain.gain.linearRampToValueAtTime(0.4, ctx.currentTime + i * 0.18 + 0.02);
                gain.gain.linearRampToValueAtTime(0, ctx.currentTime + i * 0.18 + 0.18);
                osc.start(ctx.currentTime + i * 0.18);
                osc.stop(ctx.currentTime + i * 0.18 + 0.2);
            });
        } catch (e) { /* AudioContext unavailable — fail silently */ }
    }

    function pollIncidents() {
        $.ajax({
            url: "/dashboard/incidents",
            method: "GET",
            dataType: "json",
            success: function (data) {
                var incidents = data.data || [];
                var $list  = $("#incidents-list");
                var $count = $("#incidents-count");

                // Detect new incidents (skip on first load)
                var currentIds = incidents.map(function (inc) { return inc.id; });
                if (knownIncidentIds !== null) {
                    var hasNew = currentIds.some(function (id) {
                        return knownIncidentIds.indexOf(id) === -1;
                    });
                    if (hasNew) {
                        playIncidentAlert();
                    }
                }
                knownIncidentIds = currentIds;

                if (incidents.length === 0) {
                    $list.html('<p class="text-xs text-gray-500 italic">No active incidents in the past 24 hours.</p>');
                    $count.addClass("hidden").text("");
                    return;
                }

                $count.text(incidents.length).removeClass("hidden");

                var html = '<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">';
                $.each(incidents, function (i, inc) {
                    var isSosDev  = inc.sos_from_device;
                    var isSosMob  = inc.sos_from_mobile && !inc.sos_from_device;
                    var urgVal    = inc.urgency_value;

                    // Card accent colour
                    var cardCls, headerCls, badgeCls, icon;
                    if (isSosDev) {
                        cardCls   = "rounded-lg overflow-hidden outline outline-2 -outline-offset-2 outline-red-500 bg-red-950/40";
                        headerCls = "px-4 py-3 bg-red-900/60 flex items-center gap-2";
                        badgeCls  = "bg-red-600 text-white";
                        icon      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 shrink-0 text-red-300"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>';
                    } else if (isSosMob) {
                        cardCls   = "rounded-lg overflow-hidden outline outline-2 -outline-offset-2 outline-orange-500 bg-orange-950/40";
                        headerCls = "px-4 py-3 bg-orange-900/60 flex items-center gap-2";
                        badgeCls  = "bg-orange-500 text-white";
                        icon      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 shrink-0 text-orange-300"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>';
                    } else {
                        var urgColors = { 2: ["red", "bg-red-950/40", "bg-red-900/60", "outline-red-500", "bg-red-600 text-white"],
                                          1: ["yellow", "bg-yellow-950/40", "bg-yellow-900/60", "outline-yellow-500", "bg-yellow-600 text-black"],
                                          0: ["gray",  "bg-gray-800/50",   "bg-gray-700/60",  "outline-white/10",  "bg-gray-600 text-white"] };
                        var c = urgColors[urgVal] || urgColors[0];
                        cardCls   = "rounded-lg overflow-hidden outline outline-1 -outline-offset-1 " + c[3] + " " + c[1];
                        headerCls = "px-4 py-3 " + c[2] + " flex items-center gap-2";
                        badgeCls  = c[4];
                        icon      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 shrink-0 text-gray-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>';
                    }

                    var timeStr = new Date(inc.created_at).toLocaleString(navigator.language, {
                        day: "2-digit", month: "short",
                        hour: "2-digit", minute: "2-digit", hourCycle: "h23"
                    });

                    var sosBadge = isSosDev
                        ? '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-bold ' + badgeCls + '">SOS HW</span>'
                        : isSosMob
                            ? '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-bold ' + badgeCls + '">SOS</span>'
                            : (inc.urgency_label
                                ? '<span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium ' + badgeCls + '">' + escapeHtml(inc.urgency_label) + '</span>'
                                : '');

                    // Relay row
                    var relayRow = "";
                    if (inc.nearest_relay) {
                        var gpsBtn =
                            '<button class="inc-gps-btn inline-flex items-center gap-1 rounded bg-white/10 px-2 py-1 text-xs font-medium text-white hover:bg-white/20 transition-colors shrink-0" ' +
                            'data-duck="' + escapeHtml(inc.nearest_relay) + '">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 pointer-events-none"><path fill-rule="evenodd" d="M8 1a5 5 0 0 1 5 5c0 2.813-2.45 5.714-4.168 7.603a1.145 1.145 0 0 1-1.664 0C5.45 11.714 3 8.813 3 6a5 5 0 0 1 5-5Zm0 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                            'Request GPS</button>';
                        relayRow =
                            '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;padding:0.625rem 1rem;border-top:1px solid rgba(255,255,255,0.1);">' +
                            '<span style="font-size:0.7rem;color:#6b7280;white-space:nowrap;">Nearest relay</span>' +
                            '<span style="font-family:monospace;font-size:0.7rem;color:#e5e7eb;background:rgba(255,255,255,0.1);border-radius:4px;padding:1px 6px;word-break:break-all;">' + escapeHtml(inc.nearest_relay) + '</span>' +
                            '<span style="flex:1;"></span>' +
                            gpsBtn +
                            '</div>';
                    } else if (inc.hops === 0 || inc.hops === "0") {
                        relayRow =
                            '<div style="display:flex;align-items:center;gap:0.375rem;padding:0.625rem 1rem;border-top:1px solid rgba(255,255,255,0.1);">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px;color:#4ade80;flex-shrink:0;"><path fill-rule="evenodd" d="M8 1a5 5 0 0 1 5 5c0 2.813-2.45 5.714-4.168 7.603a1.145 1.145 0 0 1-1.664 0C5.45 11.714 3 8.813 3 6a5 5 0 0 1 5-5Zm0 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                            '<span style="font-size:0.7rem;color:#4ade80;">Direct to concentrator</span>' +
                            '</div>';
                    } else {
                        relayRow =
                            '<div style="padding:0.625rem 1rem;border-top:1px solid rgba(255,255,255,0.1);">' +
                            '<span style="font-size:0.7rem;color:#4b5563;font-style:italic;">No relay path recorded</span>' +
                            '</div>';
                    }

                    var msgText = inc.display_text
                        ? '<span style="word-break:break-word;overflow-wrap:break-word;">' + escapeHtml(inc.display_text) + '</span>'
                        : '<span style="font-style:italic;color:#6b7280;">No message</span>';
                    var mapBtn = "";
                    if (inc.map_url) {
                        mapBtn =
                            '<a href="' + escapeHtml(inc.map_url) + '" target="_blank" rel="noopener noreferrer" ' +
                            'style="display:inline-flex;align-items:center;gap:0.25rem;margin-top:0.5rem;padding:0.25rem 0.625rem;border-radius:0.375rem;background:rgba(255,255,255,0.08);font-size:0.75rem;font-weight:500;color:#a5b4fc;text-decoration:none;" ' +
                            'onmouseover="this.style.background=\'rgba(255,255,255,0.15)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.08)\'">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px;flex-shrink:0;"><path fill-rule="evenodd" d="M8 1a5 5 0 0 1 5 5c0 2.813-2.45 5.714-4.168 7.603a1.145 1.145 0 0 1-1.664 0C5.45 11.714 3 8.813 3 6a5 5 0 0 1 5-5Zm0 6.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                            'View on Map</a>';
                    }

                    html +=
                        '<div style="border-radius:0.5rem;overflow:hidden;outline:2px solid ' + (isSosDev ? '#ef4444' : isSosMob ? '#f97316' : 'rgba(255,255,255,0.1)') + ';outline-offset:-2px;background:' + (isSosDev ? 'rgba(69,10,10,0.4)' : isSosMob ? 'rgba(67,20,7,0.4)' : 'rgba(31,41,55,0.5)') + ';display:flex;flex-direction:column;height:100%;">' +
                        // Header
                        '<div style="padding:0.75rem 1rem;background:' + (isSosDev ? 'rgba(127,29,29,0.6)' : isSosMob ? 'rgba(124,45,18,0.6)' : 'rgba(55,65,81,0.6)') + ';display:flex;align-items:center;gap:0.5rem;min-width:0;">' +
                        icon +
                        '<span style="font-size:0.875rem;font-weight:600;color:white;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(inc.duck_id) + '</span>' +
                        sosBadge +
                        '</div>' +
                        // Body: message, map button, timestamp
                        '<div style="padding:0.75rem 1rem 1rem;display:flex;flex-direction:column;flex:1;justify-content:space-between;">' +
                        '<div>' +
                        '<div style="font-size:0.875rem;color:#d1d5db;word-break:break-word;overflow-wrap:break-word;">' + msgText + '</div>' +
                        mapBtn +
                        '</div>' +
                        '<div style="font-size:0.7rem;color:#6b7280;margin-top:0.75rem;">' + timeStr + '</div>' +
                        '</div>' +
                        // Relay footer
                        relayRow +
                        '</div>';
                });

                html += '</div>';

                // Only repaint when content changed to avoid flicker
                if ($list.data("last-html") !== html) {
                    $list.html(html);
                    $list.data("last-html", html);
                }
            },
            error: function () {
                // Silently fail — panel stays as-is until next poll
            }
        });
    }

    // One-click GPS request from the incidents panel
    $(document).on("click", ".inc-gps-btn", function () {
        var $btn    = $(this);
        var duckId  = $btn.data("duck");
        $btn.prop("disabled", true).text("Requesting…");
        $.ajax({
            type: "POST",
            url: "/gps/request",
            data: { duck_id: duckId },
            success: function () {
                $btn.text("Sent ✓").addClass("text-green-400");
                setTimeout(function () {
                    $btn.prop("disabled", false).text("Request GPS").removeClass("text-green-400");
                }, 4000);
            },
            error: function () {
                $btn.prop("disabled", false).text("Failed").addClass("text-red-400");
                setTimeout(function () {
                    $btn.text("Request GPS").removeClass("text-red-400");
                }, 3000);
            }
        });
    });

    if ($("#incidents-list").length) {
        pollIncidents();
        setInterval(pollIncidents, 30000);
    }

    // Status page: poll /status/history and refresh each duck's message box (newest first)
    function formatHistoryMessage(msg, isRead) {
        var payload = msg.payload || "";
        var isOutbound = msg.direction === "outbound";
        var isSos = /\bSOS\b/i.test(payload);
        var isDevice = /\bSRC:DEVICE\b/i.test(payload);
        var isMsg = /^MSG\b/i.test(payload);
        var isMsgRead = /^MSG_READ\b/i.test(payload);

        // --- Operator-sent message (outbound) ---
        if (isOutbound) {
            var textMatch = payload.match(/TEXT:(.+)$/i);
            var sentText = textMatch
                ? escapeHtml(textMatch[1].trim())
                : escapeHtml(payload);
            var readTick = isRead
                ? '<span class="inline-flex items-center gap-0.5 text-xs text-blue-300 mt-0.5">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3">' +
                  '<path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>' +
                  "Received</span>"
                : '<span class="text-xs text-gray-500 mt-0.5">Sent</span>';
            return (
                '<div class="flex flex-col items-end">' +
                '<div class="rounded-md px-3 py-1.5 text-sm bg-indigo-600/70 text-white break-all max-w-full">' +
                sentText +
                "</div>" +
                readTick +
                "</div>"
            );
        }

        // Reuse the shared urgencyBadge() from the outer scope (defaults to Low when absent)
        var badge = urgencyBadge(payload);

        if (isSos && isDevice) {
            return (
                '<div class="flex items-start gap-2 rounded-md bg-red-900/50 px-3 py-2 ring-1 ring-inset ring-red-500/40">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-3.5 shrink-0 text-red-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                "<div>" +
                '<p class="text-xs font-semibold text-red-400">SOS \u2014 Hardware Button Triggered</p>' +
                '<p class="text-xs text-red-300/80">Physical SOS button was pressed on the device.</p>' +
                sosDeviceTelemetryHtml(payload) +
                "</div>" +
                "</div>"
            );
        }

        if (isSos) {
            return (
                '<div class="flex items-start gap-2 rounded-md bg-orange-900/50 px-3 py-2 ring-1 ring-inset ring-orange-500/40">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-3.5 shrink-0 text-orange-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                "<div>" +
                '<p class="text-xs font-semibold text-orange-400">SOS \u2014 Mobile Phone Triggered</p>' +
                '<p class="text-xs text-orange-300/80">SOS sent from the mobile app and should include GPS coordinates.</p>' +
                sosDeviceTelemetryHtml(payload) +
                "</div>" +
                "</div>"
            );
        }

        var isRogerDev =
            isMsg &&
            /\bSRC:DEVICE\b/i.test(payload) &&
            /\bTEXT:Roger\b/i.test(payload);

        if (isRogerDev) {
            return (
                '<div class="flex items-start gap-2 rounded-md bg-green-900/50 px-3 py-2 ring-2 ring-inset ring-green-500/60">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-3.5 shrink-0 text-green-400"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>' +
                '<div><p class="text-xs font-bold text-green-300 uppercase tracking-wide">Roger \u2014 Device Confirmed</p>' +
                '<p class="text-xs text-green-400/80">Triple-click confirmation from the device.</p></div>' +
                "</div>"
            );
        }

        if (isMsg) {
            var textMatch = payload.match(/TEXT:([^,\n]+)/i);
            var msgText = textMatch
                ? escapeHtml(textMatch[1].trim())
                : escapeHtml(msg.text || payload);
            return (
                '<div class="rounded-md px-3 py-1.5 text-sm bg-white/10 text-gray-300 break-all">' +
                msgText +
                urgencyRow(payload) +
                "</div>"
            );
        }

        var text = escapeHtml(msg.text || msg.payload || "(no content)");
        return (
            '<div class="max-w-full rounded-md px-3 py-1.5 text-sm bg-white/10 text-gray-300 break-all">' +
            text +
            "</div>"
        );
    }

    function pollHistory() {
        $.ajax({
            url: "/status/history",
            method: "GET",
            dataType: "json",
            success: function (data) {
                $.each(data, function (duckId, duck) {
                    var messages = duck.messages || [];
                    var lastCoords = duck.last_coords || null;

                    // --- Conversation history ---
                    var $box = $('[data-history-duck="' + duckId + '"]');
                    if ($box.length) {
                        if (messages.length === 0) {
                            $box.html(
                                '<p class="text-center text-xs text-gray-500">No messages yet.</p>',
                            );
                        } else {
                            // Build a set of TEXT values confirmed read — only from dcmd topic MSG_READ receipts
                            var readTexts = new Set();
                            $.each(messages, function (i, m) {
                                if (m.topic !== "dcmd") return;
                                var p = m.payload || "";
                                if (/^MSG_READ\b/i.test(p)) {
                                    var tm = p.match(/TEXT:(.+)$/i);
                                    if (tm)
                                        readTexts.add(
                                            tm[1].trim().toLowerCase(),
                                        );
                                }
                            });

                            var html = "";
                            $.each(
                                messages.slice().reverse(),
                                function (i, msg) {
                                    // MSG_READ entries only serve to build readTexts — don't render them
                                    if (/^MSG_READ\b/i.test(msg.payload || ""))
                                        return;

                                    var date = new Date(msg.created_at);
                                    var timestamp = date.toLocaleString(
                                        navigator.language,
                                        {
                                            day: "2-digit",
                                            month: "short",
                                            hour: "2-digit",
                                            minute: "2-digit",
                                            second: "2-digit",
                                            hourCycle: "h23",
                                        },
                                    );
                                    // Only outbound (operator-sent) messages can be marked as read
                                    var msgPayload = msg.payload || "";
                                    var isReadMsg = false;
                                    if (
                                        msg.direction === "outbound" &&
                                        /^MSG\b/i.test(msgPayload)
                                    ) {
                                        var tm =
                                            msgPayload.match(/TEXT:(.+)$/i);
                                        if (tm)
                                            isReadMsg = readTexts.has(
                                                tm[1].trim().toLowerCase(),
                                            );
                                    }
                                    var align =
                                        msg.direction === "outbound"
                                            ? "items-end"
                                            : "items-start";
                                    html +=
                                        '<div class="flex flex-col ' +
                                        align +
                                        ' mb-2">' +
                                        formatHistoryMessage(msg, isReadMsg) +
                                        '<span class="mt-0.5 text-xs text-gray-500">' +
                                        timestamp +
                                        "</span>" +
                                        "</div>";
                                },
                            );
                            var newHtml = html;
                            var oldHtml = $box.data("last-html") || "";
                            if (newHtml !== oldHtml) {
                                // Only auto-scroll if the user is already at (or near) the bottom
                                var el = $box[0];
                                var atBottom =
                                    el.scrollHeight -
                                        el.scrollTop -
                                        el.clientHeight <
                                    60;
                                $box.html(newHtml);
                                $box.data("last-html", newHtml);
                                if (atBottom) {
                                    $box.scrollTop(el.scrollHeight);
                                }
                            }
                        }
                    }

                    // --- Online / Offline badge ---
                    var $statusBtn = $('[data-status-duck="' + duckId + '"]');
                    var isOnline = duck.last_seen && duck.last_seen.is_online;
                    if ($statusBtn.length) {
                        $statusBtn
                            .text(isOnline ? "Online" : "Offline")
                            .toggleClass(
                                "bg-green-500 hover:bg-green-400 focus-visible:outline-green-500",
                                isOnline,
                            )
                            .toggleClass(
                                "bg-gray-500 hover:bg-gray-400 focus-visible:outline-gray-500",
                                !isOnline,
                            );
                    }
                    // Keep card data-online in sync so the Online-only toggle works live
                    var $card = $('[data-duck-id="' + duckId + '"]');
                    if ($card.length) {
                        var prevOnline = $card.attr("data-online");
                        $card.attr("data-online", isOnline ? "1" : "0");
                        // Re-apply filters if the online state changed and the toggle is active
                        if (prevOnline !== (isOnline ? "1" : "0")) {
                            var toggle = document.getElementById("online-only-toggle");
                            if (toggle && toggle.checked && typeof window.applyFilters === "function") {
                                window.applyFilters();
                            }
                        }
                    }

                    // --- Card timestamp ---
                    var $ts = $('[data-timestamp-duck="' + duckId + '"]');
                    if ($ts.length && duck.last_seen) {
                        $ts.text(duck.last_seen.created_at_for_humans);
                    }

                    // --- Card body (message text / SOS banners) ---
                    var $cardBody = $('[data-card-body-duck="' + duckId + '"]');
                    if ($cardBody.length && messages.length > 0) {
                        var cardMsg = null;
                        for (var ci = 0; ci < messages.length; ci++) {
                            var m = messages[ci];
                            if (m.direction === "outbound") continue;
                            if (/^MSG_READ\b/i.test(m.payload || "")) continue;
                            cardMsg = m;
                            break;
                        }
                        if (cardMsg) {
                            var cp = cardMsg.payload || "";
                            var cisSosDev =
                                /\bSOS\b/i.test(cp) &&
                                /\bSRC:DEVICE\b/i.test(cp);
                            var cisSosMob =
                                /\bSOS\b/i.test(cp) &&
                                !/\bSRC:DEVICE\b/i.test(cp);
                            var cisRogerDev =
                                /^MSG\b/i.test(cp) &&
                                /\bSRC:DEVICE\b/i.test(cp) &&
                                /\bTEXT:Roger\b/i.test(cp);
                            var bodyHtml;
                            if (cisSosDev) {
                                bodyHtml =
                                    '<div class="flex items-start gap-2 rounded-md bg-red-900/50 px-3 py-2 ring-1 ring-inset ring-red-500/40">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-xs font-semibold text-red-400">SOS \u2014 Hardware Button Triggered</p>' +
                                    '<p class="text-xs text-red-300/80">This SOS was sent because the physical SOS button on the device was pressed.</p>' +
                                    sosDeviceTelemetryHtml(cp) +
                                    "</div>" +
                                    "</div>";
                            } else if (cisSosMob) {
                                bodyHtml =
                                    '<div class="flex items-start gap-2 rounded-md bg-orange-900/50 px-3 py-2 ring-1 ring-inset ring-orange-500/40">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-orange-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-xs font-semibold text-orange-400">SOS \u2014 Mobile Phone Triggered</p>' +
                                    '<p class="text-xs text-orange-300/80">This SOS was sent from the user\'s mobile phone application and should include GPS coordinates.</p>' +
                                    sosDeviceTelemetryHtml(cp) +
                                    "</div>" +
                                    "</div>";
                            } else if (cisRogerDev) {
                                bodyHtml =
                                    '<div class="flex items-start gap-2 rounded-md bg-green-900/50 px-3 py-2 ring-2 ring-inset ring-green-500/60">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-green-400"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-sm font-bold text-green-300 uppercase tracking-wide">Roger \u2014 Device Confirmed</p>' +
                                    '<p class="text-xs text-green-400/80">The person holding the device triple-clicked the button to confirm they have received your message.</p></div>' +
                                    "</div>";
                            } else {
                                var displayText =
                                    cardMsg.text || cardMsg.payload || "";
                                bodyHtml =
                                    '<p class="text-sm text-gray-400 break-words">' +
                                    escapeHtml(displayText) +
                                    "</p>";
                            }
                            var newBodyHtml = bodyHtml;
                            if ($cardBody.data("last-html") !== newBodyHtml) {
                                $cardBody.html(newBodyHtml);
                                $cardBody.data("last-html", newBodyHtml);
                            }
                        }
                    }

                    // --- Critical MSG urgency notice ---
                    var $urgencyNotice = $(
                        '[data-urgency-notice-duck="' + duckId + '"]',
                    );
                    if ($urgencyNotice.length && messages.length > 0) {
                        var cardMsg2 = null;
                        for (var ci2 = 0; ci2 < messages.length; ci2++) {
                            var m2 = messages[ci2];
                            if (m2.direction === "outbound") continue;
                            if (/^MSG_READ\b/i.test(m2.payload || "")) continue;
                            cardMsg2 = m2;
                            break;
                        }
                        var latestPayload = cardMsg2
                            ? cardMsg2.payload || ""
                            : "";
                        var isMsg = /^MSG\b/i.test(latestPayload);
                        var urgencyM = latestPayload.match(/URGENCY:(\d)/i);
                        var isCritical =
                            isMsg && urgencyM && urgencyM[1] === "2";

                        if (isCritical) {
                            $urgencyNotice.html(
                                '<div class="mt-2 flex items-start gap-2 rounded-md bg-red-950 px-3 py-2 ring-2 ring-inset ring-red-500 animate-pulse">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                                    "<div>" +
                                    '<p class="text-xs font-bold text-red-400 uppercase tracking-wide">Critical \u2014 Immediate Attention Required</p>' +
                                    '<p class="text-xs text-red-300/80">This message has been marked as critical urgency and must be attended to immediately.</p>' +
                                    "</div>" +
                                    "</div>",
                            );
                        } else {
                            $urgencyNotice.empty();
                        }
                    }

                    // --- Card critical styling (toggle based on live urgency) ---
                    var $card = $('[data-duck-id="' + duckId + '"]');
                    var $header = $card.children().first();
                    var $duckId = $header.find("span").first();
                    var latestPayloadForStyle = cardMsg2
                        ? cardMsg2.payload || ""
                        : "";
                    var isCriticalCard =
                        /^MSG\b/i.test(latestPayloadForStyle) &&
                        (function () {
                            var m =
                                latestPayloadForStyle.match(/URGENCY:(\d)/i);
                            return m && m[1] === "2";
                        })();

                    if (isCriticalCard) {
                        $card.attr(
                            "class",
                            "critical-card flex flex-col divide-y divide-red-500/30 overflow-hidden rounded-lg bg-red-950/40 outline outline-2 -outline-offset-2 outline-red-500",
                        );
                        $header.attr(
                            "class",
                            "px-4 py-4 sm:px-6 flex flex-col gap-2 bg-red-900/50",
                        );
                        $duckId.attr(
                            "class",
                            "text-sm font-bold text-red-300 tracking-wide",
                        );
                    } else {
                        $card.attr(
                            "class",
                            "flex flex-col divide-y divide-white/10 overflow-hidden rounded-lg bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10",
                        );
                        $header.attr(
                            "class",
                            "px-4 py-4 sm:px-6 flex flex-col gap-2",
                        );
                        $duckId.attr(
                            "class",
                            "text-sm font-semibold text-white",
                        );
                    }

                    // --- Last known GPS ---
                    // Update the GPS warning text (no-hardware / no-fix) dynamically.
                    var $gpsWarning = $(
                        '[data-gps-warning-duck="' + duckId + '"]',
                    );
                    if ($gpsWarning.length) {
                        var warnHtml = "";
                        if (duck.gps_hardware_absent) {
                            warnHtml =
                                '<p class="mt-2 inline-flex items-center gap-1.5 text-xs text-gray-500">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd"/></svg>' +
                                "No GPS hardware \u2014 this device cannot report location</p>";
                        } else if (duck.gps_unavailable && !lastCoords) {
                            warnHtml =
                                '<p class="mt-2 inline-flex items-center gap-1.5 text-xs text-yellow-400">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                                "GPS location unavailable \u2014 no satellite fix</p>";
                        }
                        if ($gpsWarning.data("last-warn") !== warnHtml) {
                            $gpsWarning.html(warnHtml);
                            $gpsWarning.data("last-warn", warnHtml);
                        }
                    }
                    // Show/hide the "View on Map" button based on whether the current record has GPS.
                    var $mapBtn = $('[data-map-btn-duck="' + duckId + '"]');
                    if ($mapBtn.length) {
                        var showMapBtn =
                            !duck.gps_hardware_absent &&
                            lastCoords &&
                            lastCoords.map_url;
                        $mapBtn.toggleClass("hidden", !showMapBtn);
                    }

                    var $gps = $('[data-gps-duck="' + duckId + '"]');
                    if ($gps.length) {
                        if (duck.gps_hardware_absent) {
                            var noGpsHtml =
                                '<p class="text-xs text-gray-500" style="display:flex;align-items:center;gap:0.375rem;">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd"/></svg>' +
                                "No GPS hardware \u2014 location unavailable" +
                                "</p>";
                            if ($gps.data("last-html") !== noGpsHtml) {
                                $gps.html(noGpsHtml);
                                $gps.data("last-html", noGpsHtml);
                            }
                        } else if (duck.gps_unavailable && !lastCoords) {
                            var noFixHtml =
                                '<p class="text-xs text-yellow-500" style="display:flex;align-items:center;gap:0.375rem;">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                                "No satellite fix \u2014 waiting for GPS signal" +
                                "</p>";
                            if ($gps.data("last-html") !== noFixHtml) {
                                $gps.html(noFixHtml);
                                $gps.data("last-html", noFixHtml);
                            }
                        } else if (lastCoords) {
                            var embedUrl = lastCoords.map_url.replace(
                                "maps?q=",
                                "maps?output=embed&q=",
                            );
                            var noFixBanner = duck.gps_unavailable
                                ? '<div style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;background:rgba(234,179,8,0.1);font-size:0.7rem;color:#ca8a04;border-bottom:1px solid rgba(234,179,8,0.2);">' +
                                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px;flex-shrink:0"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd"/></svg>' +
                                  "No satellite fix \u2014 showing last known location" +
                                  "</div>"
                                : "";
                            var cachedUrl = $gps.attr("data-cached-map-url");
                            if (
                                cachedUrl !== lastCoords.map_url ||
                                $gps.attr("data-no-fix") !==
                                    String(duck.gps_unavailable)
                            ) {
                                $gps.attr(
                                    "data-cached-map-url",
                                    lastCoords.map_url,
                                );
                                $gps.attr(
                                    "data-no-fix",
                                    String(duck.gps_unavailable),
                                );
                                $gps.html(
                                    '<div class="rounded-md overflow-hidden outline outline-1 -outline-offset-1 outline-white/10">' +
                                        noFixBanner +
                                        '<div style="display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,0.05);padding:0.5rem 1.25rem 0.5rem 1rem">' +
                                        '<div style="display:flex;flex-direction:column;gap:1px;font-size:0.75rem;color:#9ca3af">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;color:#4ade80;flex-shrink:0;display:none"></svg>' +
                                        "<span>Last known location</span>" +
                                        '<span class="gps-age" style="font-size:0.7rem;color:#6b7280">' +
                                        escapeHtml(
                                            lastCoords.created_at_for_humans,
                                        ) +
                                        "</span>" +
                                        "</div>" +
                                        '<div style="display:flex;align-items:center;gap:6px">' +
                                        (lastCoords.lat && lastCoords.lng
                                            ? '<button type="button" class="gps-copy-coords" data-lat="' +
                                              escapeHtml(lastCoords.lat) +
                                              '" data-lng="' +
                                              escapeHtml(lastCoords.lng) +
                                              '" title="Copy coordinates">' +
                                              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px;display:inline;vertical-align:middle"><path d="M3.5 2A1.5 1.5 0 0 0 2 3.5v9A1.5 1.5 0 0 0 3.5 14h5a1.5 1.5 0 0 0 1.5-1.5v-1H11a1.5 1.5 0 0 0 1.5-1.5v-5l-3-3H7A1.5 1.5 0 0 0 5.5 3H5V2H3.5zm4 1H8v2.5A.5.5 0 0 0 8.5 6H11v4.5a.5.5 0 0 1-.5.5h-1V8.5A1.5 1.5 0 0 0 8 7H4V3.5a.5.5 0 0 1 .5-.5H7.5z"/></svg>' +
                                              "</button>"
                                            : "") +
                                        '<a href="' +
                                        escapeHtml(lastCoords.map_url) +
                                        '" target="_blank" rel="noopener noreferrer" class="gps-toggle-map">Open in Maps</a>' +
                                        "</div>" +
                                        "</div>" +
                                        '<iframe src="' +
                                        escapeHtml(embedUrl) +
                                        '" class="w-full border-0" style="height:180px" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' +
                                        "</div>",
                                );
                            } else {
                                // Just refresh the "X ago" label without re-rendering the whole block
                                $gps.find(".gps-age").text(
                                    lastCoords.created_at_for_humans,
                                );
                            }

                            // Sync the map dialog header + iframe with the latest GPS data
                            var $dlg = $(
                                'dialog[data-map-duck="' + duckId + '"]',
                            );
                            if ($dlg.length) {
                                // Only swap the iframe src when there is a valid embed URL and it
                                // differs from the current one.  Compare via endsWith() because
                                // browsers normalise iframe.src to an absolute URL while the API
                                // may return a relative path.
                                var dlgIframe =
                                    $dlg.find("[data-map-iframe]")[0];
                                if (
                                    dlgIframe &&
                                    lastCoords.map_embed_url &&
                                    !dlgIframe.src.endsWith(
                                        lastCoords.map_embed_url,
                                    )
                                ) {
                                    dlgIframe.src = lastCoords.map_embed_url;
                                }
                                // Update the external link only when a URL is available
                                if (lastCoords.map_url) {
                                    $dlg.find("[data-map-ext-link]").attr(
                                        "href",
                                        lastCoords.map_url,
                                    );
                                }
                                // Rebuild the subtitle: Source · satellites · alt · spd · hdg
                                var srcCls = lastCoords.gps_from_phone
                                    ? "text-blue-400"
                                    : "text-green-400";
                                var sub =
                                    'Source: <span class="' +
                                    srcCls +
                                    '">' +
                                    escapeHtml(
                                        String(
                                            lastCoords.gps_source_label || "",
                                        ),
                                    ) +
                                    "</span>";
                                if (
                                    lastCoords.gps_sats !== null &&
                                    lastCoords.gps_sats !== undefined
                                ) {
                                    sub +=
                                        " &bull; " +
                                        escapeHtml(
                                            String(lastCoords.gps_sats),
                                        ) +
                                        " satellites";
                                }
                                if (
                                    lastCoords.gps_alt !== null &&
                                    lastCoords.gps_alt !== undefined
                                ) {
                                    sub +=
                                        " &bull; " +
                                        Number(lastCoords.gps_alt).toFixed(1) +
                                        " m";
                                    sub +=
                                        " &bull; " +
                                        Number(lastCoords.gps_spd || 0).toFixed(
                                            1,
                                        ) +
                                        " km/h";
                                    sub +=
                                        " &bull; " +
                                        Number(lastCoords.gps_hdg || 0).toFixed(
                                            1,
                                        ) +
                                        "&deg;";
                                }
                                $dlg.find("[data-map-subtitle]").html(sub);
                            }
                        } else {
                            $gps.html(
                                '<p class="text-xs text-gray-600">No GPS coordinates received yet.</p>',
                            );
                        }
                    }
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error(
                    "Error fetching history: " + textStatus,
                    errorThrown,
                );
            },
        });
    }

    if ($("[data-history-duck]").length) {
        pollHistory();
        setInterval(pollHistory, 3000);

        // Scroll history to bottom when the message modal is first opened
        document
            .querySelectorAll('dialog[id^="msg-dialog-"]')
            .forEach(function (dialog) {
                dialog.addEventListener("toggle", function (e) {
                    if (e.newState === "open") {
                        var duckId = dialog.id.replace("msg-dialog-", "");
                        var box = document.querySelector(
                            '[data-history-duck="' + duckId + '"]',
                        );
                        if (box) box.scrollTop = box.scrollHeight;
                    }
                });
            });
    }

    $(document).on("click", ".gps-copy-coords", function () {
        var lat = $(this).data("lat");
        var lng = $(this).data("lng");
        var text = lat + ", " + lng;
        var $btn = $(this);
        navigator.clipboard.writeText(text).then(function () {
            $btn.attr("title", "Copied!");
            $btn.css("color", "#4ade80");
            setTimeout(function () {
                $btn.attr("title", "Copy coordinates");
                $btn.css("color", "");
            }, 2000);
        });
    });

    $(document).on("keydown", ".duck-message-form textarea", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            $(this).closest(".duck-message-form").trigger("submit");
        }
    });

    $(document).on("submit", ".duck-message-form", function (e) {
        e.preventDefault();

        var $form = $(this);
        var $textarea = $form.find('textarea[name="message"]');
        var $submit = $form.find('button[type="submit"]');
        var $status = $form.find(".send-status");
        var formData = $form.serialize();
        var actionUrl = $form.attr("action");

        if ($textarea.val().trim() === "") return;

        // Disable input while sending
        $textarea.prop("disabled", true);
        $submit.prop("disabled", true).text("Sending…");
        $status
            .text("")
            .removeClass("text-green-400 text-red-400")
            .addClass("text-yellow-400")
            .text("Sending…");

        $.ajax({
            type: "POST",
            url: actionUrl,
            data: formData,
            success: function (response) {
                $form[0].reset();
                $status
                    .removeClass("text-yellow-400 text-red-400")
                    .addClass("text-green-400")
                    .text("Message sent.");
                setTimeout(function () {
                    $status.text("");
                }, 3000);
            },
            error: function (xhr, status, error) {
                $status
                    .removeClass("text-yellow-400 text-green-400")
                    .addClass("text-red-400")
                    .text("Failed to send. Try again.");
            },
            complete: function () {
                $textarea.prop("disabled", false);
                $submit.prop("disabled", false).text("Send Message");
            },
        });
    });
});
