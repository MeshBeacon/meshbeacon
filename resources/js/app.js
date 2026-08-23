//import ApexCharts from 'apexcharts'

// True on a hybrid-deployment central aggregator instance (DASHBOARD_READONLY
// env var) — see docs/HYBRID_DEPLOYMENT.md. Incident dispatch (acknowledge,
// assign, notes, resolve) only ever happens at the field site; this flag
// gates the corresponding UI here. The real enforcement is server-side
// (PreventDashboardWritesWhenReadonly middleware) — this is just UX so
// central operators aren't confused by controls that would 403.
var DASHBOARD_READONLY = document.body && document.body.dataset.dashboardReadonly === "1";

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
            url: "/messages/json",
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
                        '<div class="font-medium text-gray-900 dark:text-white">' +
                        data +
                        '</div><div class="absolute -top-px left-6 right-0 h-px bg-gray-200 dark:bg-white/10"></div>'
                    );
                },
            },
            {
                data: "created_at",
                defaultContent: "",
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
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
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "message_id",
                defaultContent: "",
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "path",
                defaultContent: "",
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
                render: function (data) {
                    if (!data) return '<span class="italic text-gray-400 dark:text-gray-600">&mdash;</span>';
                    var hops = data.split(',');
                    var parts = hops.map(function (hop, i) {
                        return (i > 0 ? '<span class="text-gray-400 dark:text-gray-500 mx-0.5">&#8594;</span>' : '') +
                            '<span class="rounded bg-gray-100 dark:bg-white/10 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:text-gray-200">' +
                            escapeHtml(hop.trim()) + '</span>';
                    });
                    return '<div class="flex flex-wrap items-center gap-1">' + parts.join('') + '</div>';
                },
            },
            {
                data: "display_text",
                defaultContent: "",
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell",
                render: function (data, type, row) {
                    // RREP rows have no message payload — show origin → destination instead
                    if (row.topic === "rrep") {
                        var origin = row.origin || "?";
                        var dest   = row.destination || "?";
                        return (
                            '<div style="display:flex;align-items:center;gap:0.375rem;">' +
                            '<span style="flex-shrink:0;" class="inline-flex items-center justify-center rounded bg-purple-700 px-1.5 py-0.5 text-xs font-bold text-white">RREP</span>' +
                            '<span class="font-mono text-xs text-gray-700 dark:text-gray-300">' +
                            escapeHtml(origin) +
                            '</span>' +
                            '<span class="text-gray-400 dark:text-gray-500">&#8594;</span>' +
                            '<span class="font-mono text-xs text-gray-700 dark:text-gray-300">' +
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
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "duck_type",
                defaultContent: "",
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm text-gray-500 dark:text-gray-400 lg:table-cell dt-type-date sorting_1",
            },
            {
                data: "urgency_label",
                defaultContent: "",
                orderable: false,
                className:
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm lg:table-cell",
                render: function (data, type, row) {
                    if (data == null)
                        return '<span class="text-gray-400 dark:text-gray-600">&mdash;</span>';
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
                    "hidden border-t border-gray-200 dark:border-white/10 px-3 py-3.5 text-sm lg:table-cell",
                render: function (data, type, row) {
                    if (!data)
                        return '<span class="text-gray-400 dark:text-gray-600">&mdash;</span>';
                    return (
                        '<button class="dt-map-btn inline-flex items-center gap-1 rounded-md bg-gray-200 dark:bg-white/10 px-2 py-1 text-xs font-semibold text-gray-900 dark:text-white hover:bg-gray-300 dark:hover:bg-white/20" data-embed="' +
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
                '<div class="p-3 bg-gray-100 dark:bg-gray-900">' +
                    '<iframe src="' +
                    url +
                    '" class="w-full h-64 rounded-md border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' +
                    "</div>",
            ).show();
            tr.addClass("dt-map-shown");
        }
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
            cls: "bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-400 ring-green-300 dark:ring-green-500/30",
        },
        1: {
            label: "Medium",
            cls: "bg-yellow-100 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-400 ring-yellow-300 dark:ring-yellow-500/30",
        },
        2: {
            label: "Critical",
            cls: "bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-400 ring-red-300 dark:ring-red-500/30",
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
        var html = '<div class="mt-1.5 flex flex-col gap-1.5">';
        if (battM) {
            var b = parseInt(battM[1], 10);
            var battCls =
                b < 20
                    ? "bg-red-200 dark:bg-red-800/60 text-red-800 dark:text-red-300"
                    : b < 50
                      ? "bg-orange-200 dark:bg-orange-800/60 text-orange-800 dark:text-orange-300"
                      : "bg-green-200 dark:bg-green-800/60 text-green-800 dark:text-green-300";
            html +=
                '<div class="flex items-start gap-1.5">' +
                '<span class="text-xs text-gray-600 dark:text-gray-500 w-10 shrink-0 pt-0.5">Device</span>' +
                '<div class="flex flex-wrap gap-1.5 flex-1">' +
                '<span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium ' +
                battCls +
                '">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>' +
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
                    '<span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-blue-200 dark:bg-blue-800/60 text-blue-800 dark:text-blue-200">' +
                    parseFloat(altM[1]).toFixed(1) +
                    " m alt" +
                    "</span>";
            }
            if (spdM) {
                gpsPills +=
                    '<span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-purple-200 dark:bg-purple-800/60 text-purple-800 dark:text-purple-200">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>' +
                    parseFloat(spdM[1]).toFixed(1) +
                    " km/h" +
                    "</span>";
            }
            if (hdgM) {
                gpsPills +=
                    '<span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-sky-200 dark:bg-sky-800/60 text-sky-800 dark:text-sky-200">' +
                    parseFloat(hdgM[1]).toFixed(1) +
                    "\u00b0" +
                    "</span>";
            }
            html +=
                '<div class="flex items-start gap-1.5">' +
                '<span class="text-xs text-gray-600 dark:text-gray-500 w-10 shrink-0 pt-0.5">GPS</span>' +
                '<div class="flex flex-wrap gap-1.5 flex-1">' +
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
            '<span class="text-gray-500 dark:text-gray-400">Urgency:</span>' +
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
                        '<li><div class="relative pb-8"><div class="relative flex space-x-3"><div><img src="/images/logo-small.png" alt="Logo" class="size-10"></div><div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5"><div class="min-w-0 flex-1">' +
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

    // ── Browser push notifications ──────────────────────────────────────────────
    function requestNotificationPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(function () {
                var btn = document.getElementById('notif-btn');
                if (btn) btn.style.display = 'none';
            });
        }
    }
    window.requestNotificationPermission = requestNotificationPermission;
    // Hide the button immediately if permission already granted/denied
    (function () {
        if ('Notification' in window && Notification.permission !== 'default') {
            var btn = document.getElementById('notif-btn');
            if (btn) btn.style.display = 'none';
        }
    })();

    function firePushNotification(title, body) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        new Notification(title, {
            body:              body || '',
            icon:              '/images/logo.png',
            tag:               'sos-alert',
            requireInteraction: true,
        });
    }

    // Active Incidents panel — polls /dashboard/incidents every 30 s.
    // Shows the latest alert per duck from the past 24 h with the nearest
    // relay duck highlighted and a one-click GPS request button.
    var knownIncidentIds = null; // null = first load (don't alert on initial paint)
    var lastIncidentsRaw = []; // cache of the last /dashboard/incidents payload, for client-side search filtering

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

    // Responders list (cached) for the incident assignment dropdown.
    var incidentResponders = [];
    function loadResponders() {
        $.ajax({
            url: "/dashboard/incidents/responders",
            method: "GET",
            dataType: "json",
            success: function (data) {
                incidentResponders = data || [];
                // loadResponders() and pollIncidents() both fire on page
                // load; if this responders request resolves after the
                // first incidents render, the assign dropdown would be
                // stuck showing only "Unassigned" until the next 30s poll.
                // Re-render immediately (from cached data, no extra
                // request) once responders are actually available.
                renderIncidentsList();
            }
        });
    }

    function buildAssignSelect(inc) {
        var checkIcon = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4">' +
            '<path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />' +
            '</svg>';
        function optionHtml(value, label) {
            return '<el-option value="' + value + '" class="group/option relative cursor-default select-none py-1.5 pl-6 pr-3 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">' +
                '<span class="block truncate text-xs font-normal group-aria-selected/option:font-semibold">' + escapeHtml(label) + '</span>' +
                '<span class="absolute inset-y-0 left-0 flex items-center pl-1 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected=\'true\'])]/option:hidden [el-selectedcontent_&]:hidden">' +
                checkIcon +
                '</span>' +
                '</el-option>';
        }

        var currentId    = inc.assigned_to ? String(inc.assigned_to) : '';
        var currentLabel = 'Unassigned';
        var optionsHtml  = optionHtml('', 'Unassigned');
        incidentResponders.forEach(function (u) {
            if (String(u.id) === currentId) currentLabel = u.name;
            optionsHtml += optionHtml(u.id, u.name);
        });

        return '<el-select name="assign-' + escapeHtml(inc.message_id) + '" value="' + currentId + '" ' +
            'class="inc-assign-select block w-28 shrink-0" data-msgid="' + escapeHtml(inc.message_id) + '">' +
            '<button type="button" class="grid w-full cursor-default grid-cols-1 rounded bg-white/5 py-0.5 pl-2 pr-1 text-left text-xs text-gray-300 outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500">' +
                '<el-selectedcontent class="col-start-1 row-start-1 truncate pr-4">' + escapeHtml(currentLabel) + '</el-selectedcontent>' +
                '<svg viewBox="0 0 16 16" fill="currentColor" class="col-start-1 row-start-1 size-3.5 self-center justify-self-end text-gray-400"><path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" /></svg>' +
            '</button>' +
            '<el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">' +
                optionsHtml +
            '</el-options>' +
        '</el-select>';
    }

    function pollIncidents() {
        // Don't repaint while the operator is actively editing a note — the
        // regenerated HTML would otherwise clobber their in-progress text.
        if (document.activeElement && document.activeElement.classList &&
            document.activeElement.classList.contains('inc-notes-input')) {
            return;
        }
        $.ajax({
            url: "/dashboard/incidents",
            method: "GET",
            dataType: "json",
            success: function (data) {
                var incidentsAll = data.data || [];
                lastIncidentsRaw = incidentsAll;

                // Detect new incidents (skip on first load)
                var currentIds = incidentsAll.map(function (inc) { return inc.id; });
                if (knownIncidentIds !== null) {
                    var newIds = currentIds.filter(function (id) {
                        return knownIncidentIds.indexOf(id) === -1;
                    });
                    if (newIds.length) {
                        playIncidentAlert();
                        newIds.forEach(function (id) {
                            var ni = incidentsAll.find(function (i) { return i.id === id; });
                            if (ni) firePushNotification('\uD83D\uDEA8 SOS from ' + ni.duck_id, ni.display_text || 'No message');
                        });
                    }
                }
                knownIncidentIds = currentIds;

                renderIncidentsList();
            },
            error: function () {
                // Silently fail — panel stays as-is until next poll
            }
        });
    }

    // Combined keyword search across duck ID, message text, notes, and
    // assigned responder name — one search box rather than separate
    // per-field filters, since operators typically search for "everything
    // about DUCK3" or "medevac" rather than a specific column.
    function filterIncidents(incidents, query) {
        if (!query) return incidents;
        return incidents.filter(function (inc) {
            var haystack = [inc.duck_id, inc.display_text, inc.incident_notes, inc.assigned_to_name]
                .filter(Boolean).join(' ').toLowerCase();
            return haystack.indexOf(query) !== -1;
        });
    }

    // Ranks incidents so the most urgent/newest are always shown first —
    // matters most on the kiosk screen, where the list may be capped and
    // whatever sorts to the top must be the stuff that actually needs eyes.
    function incidentSortRank(inc) {
        if (inc.sos_from_device) return 4;
        if (inc.sos_from_mobile) return 3;
        if (inc.urgency_value === 2) return 2;
        if (inc.urgency_value === 1) return 1;
        return 0;
    }

    function sortIncidents(list) {
        return list.slice().sort(function (a, b) {
            var rankDiff = incidentSortRank(b) - incidentSortRank(a);
            if (rankDiff !== 0) return rankDiff;
            return new Date(b.created_at) - new Date(a.created_at);
        });
    }

    // Renders the incidents panel from the last-fetched data, applying the
    // current search filter. Called after every poll and whenever the
    // search box changes — filtering is done client-side against the
    // already-fetched data, so typing doesn't trigger extra network calls.
    function renderIncidentsList() {
        var $list  = $("#incidents-list");
        var $count = $("#incidents-count");
        var query  = ($("#incidents-search").val() || "").trim().toLowerCase();
        var isCompact = $list.is('[data-compact]');
        var incidents = sortIncidents(filterIncidents(lastIncidentsRaw, query));

        if (lastIncidentsRaw.length === 0) {
            $list.html('<p class="text-xs text-gray-500 italic">No active incidents in the past 24 hours.</p>');
            $count.addClass("hidden").text("");
            $list.data("last-html", null);
            return;
        }

        if (incidents.length === 0) {
            $list.html('<p class="text-xs text-gray-500 italic">No incidents match your search.</p>');
            $count.text('0 of ' + lastIncidentsRaw.length).removeClass("hidden");
            $list.data("last-html", null);
            return;
        }

        $count.text(query ? incidents.length + ' of ' + lastIncidentsRaw.length : incidents.length).removeClass("hidden");

        // Kiosk mode is view-only and has limited screen space — cap the
        // rendered cards to a sane number (already sorted urgent-first) and
        // say how many more there are, rather than silently overflowing
        // off-screen with no indication anything is missing.
        var displayIncidents = incidents;
        var hiddenCount = 0;
        if (isCompact) {
            var KIOSK_CAP = 9;
            if (incidents.length > KIOSK_CAP) {
                hiddenCount = incidents.length - KIOSK_CAP;
                displayIncidents = incidents.slice(0, KIOSK_CAP);
            }
        }

        var html = '<div class="grid grid-cols-1 gap-3 @xl:grid-cols-2 @5xl:grid-cols-3">';
        $.each(displayIncidents, function (i, inc) {
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

                    // Incident lifecycle status badge
                    var incStatus = inc.incident_status || 'open';
                    var statusStyleMap = {
                        'open':         'background:rgba(75,85,99,0.7);color:#d1d5db',
                        'acknowledged': 'background:rgba(120,53,15,0.8);color:#fde68a',
                        'responding':   'background:rgba(30,58,138,0.8);color:#93c5fd',
                        'resolved':     'background:rgba(20,83,45,0.8);color:#86efac',
                    };
                    var statusBadge = '<span style="font-size:0.65rem;padding:1px 7px;border-radius:3px;font-weight:600;' + (statusStyleMap[incStatus] || statusStyleMap['open']) + '">' + incStatus.toUpperCase() + '</span>';

                    // Retransmission indicator — shown when the duck has re-sent
                    // its SOS more than once while this incident is still open
                    // (may mean the ACK isn't reaching the device).
                    var retransCount = inc.retransmission_count || 1;
                    var retransBadge = retransCount > 1
                        ? '<span title="' + retransCount + ' SOS transmissions received for this incident" ' +
                          'style="font-size:0.65rem;padding:1px 7px;border-radius:3px;font-weight:600;background:rgba(255,255,255,0.1);color:#e5e7eb;">\u00d7' + retransCount + '</span>'
                        : '';

                    // Actions row (ACK + lifecycle buttons)
                    var actionsHtml = '';
                    if (incStatus !== 'resolved') {
                        var btnBase = 'display:inline-flex;align-items:center;gap:3px;padding:2px 10px;border-radius:4px;font-size:0.7rem;font-weight:600;cursor:pointer;border:none;transition:opacity 0.15s;';
                        var ackHtml = incStatus === 'open'
                            ? '<button class="inc-ack-btn" data-duck="' + escapeHtml(inc.duck_id) + '" data-msgid="' + escapeHtml(inc.message_id) + '" style="' + btnBase + 'background:rgba(239,68,68,0.25);color:#fca5a5;">📡 Re-send ACK</button>'
                            : '';
                        var transMap = {
                            'open':         [['acknowledged','Acknowledge'],['responding','Responding']],
                            'acknowledged': [['responding','Responding'],['resolved','Resolved ✓']],
                            'responding':   [['resolved','Resolved ✓']],
                        };
                        var statusBtns = (transMap[incStatus] || []).map(function(pair) {
                            return '<button class="inc-status-btn" data-msgid="' + escapeHtml(inc.message_id) + '" data-status="' + pair[0] + '" style="' + btnBase + 'background:rgba(255,255,255,0.08);color:#d1d5db;">→ ' + pair[1] + '</button>';
                        }).join('');
                        actionsHtml = '<div style="display:flex;flex-wrap:wrap;gap:6px;padding:0.5rem 1rem 0.625rem;border-top:1px solid rgba(255,255,255,0.08);">' + ackHtml + statusBtns + '</div>';
                    }

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

                    // Assignment + notes row (available regardless of status
                    // so the incident stays auditable after resolution).
                    var assignNotesHtml =
                        '<div style="display:flex;flex-direction:column;gap:6px;padding:0.5rem 1rem 0.625rem;border-top:1px solid rgba(255,255,255,0.08);">' +
                        '<div style="display:flex;align-items:center;gap:6px;">' +
                        '<span style="font-size:0.65rem;color:#6b7280;white-space:nowrap;">Assigned to</span>' +
                        buildAssignSelect(inc) +
                        '</div>' +
                        '<div style="display:flex;gap:6px;">' +
                        '<input type="text" class="inc-notes-input" data-msgid="' + escapeHtml(inc.message_id) + '" value="' + escapeHtml(inc.incident_notes || '') + '" placeholder="Add a note\u2026" ' +
                        'style="flex:1;min-width:0;font-size:0.7rem;background:rgba(255,255,255,0.06);color:#e5e7eb;border:none;border-radius:4px;padding:4px 8px;" />' +
                        '<button class="inc-notes-save" data-msgid="' + escapeHtml(inc.message_id) + '" ' +
                        'style="font-size:0.7rem;padding:4px 10px;border-radius:4px;background:rgba(255,255,255,0.1);color:#d1d5db;border:none;cursor:pointer;">Save</button>' +
                        '</div>' +
                        '</div>';

                    if (isCompact) {
                        // Condensed card for the kiosk screen — no operator
                        // controls (ack/status/assign/notes), just enough to
                        // tell what/who/how urgent/when at a glance. The
                        // kiosk is ops-room-only (not public-facing), so
                        // showing WHO is assigned is useful for the room to
                        // coordinate — but kept subtle (small, muted, and
                        // omitted entirely when unassigned) so it doesn't
                        // compete with the SOS/status badges.
                        var assigneeLine = inc.assigned_to_name
                            ? '<div style="font-size:0.68rem;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">&rarr; ' + escapeHtml(inc.assigned_to_name) + '</div>'
                            : '';
                        html +=
                            '<div style="border-radius:0.5rem;overflow:hidden;outline:2px solid ' + (isSosDev ? '#ef4444' : isSosMob ? '#f97316' : 'rgba(255,255,255,0.1)') + ';outline-offset:-2px;background:' + (isSosDev ? 'rgba(69,10,10,0.4)' : isSosMob ? 'rgba(67,20,7,0.4)' : 'rgba(31,41,55,0.5)') + ';padding:0.625rem 0.875rem;display:flex;flex-direction:column;gap:4px;">' +
                            '<div style="display:flex;align-items:center;gap:0.5rem;min-width:0;">' +
                            icon +
                            '<span style="font-size:0.85rem;font-weight:600;color:white;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(inc.duck_id) + '</span>' +
                            sosBadge +
                            statusBadge +
                            '</div>' +
                            '<div style="font-size:0.78rem;color:#d1d5db;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">' + msgText + '</div>' +
                            assigneeLine +
                            '<div style="font-size:0.68rem;color:#6b7280;">' + timeStr + '</div>' +
                            '</div>';
                        return;
                    }

                    html +=
                        '<div style="border-radius:0.5rem;overflow:hidden;outline:2px solid ' + (isSosDev ? '#ef4444' : isSosMob ? '#f97316' : 'rgba(255,255,255,0.1)') + ';outline-offset:-2px;background:' + (isSosDev ? 'rgba(69,10,10,0.4)' : isSosMob ? 'rgba(67,20,7,0.4)' : 'rgba(31,41,55,0.5)') + ';display:flex;flex-direction:column;height:100%;">' +
                        // Header
                        '<div style="padding:0.75rem 1rem;background:' + (isSosDev ? 'rgba(127,29,29,0.6)' : isSosMob ? 'rgba(124,45,18,0.6)' : 'rgba(55,65,81,0.6)') + ';display:flex;align-items:center;gap:0.5rem;min-width:0;">' +
                        icon +
                        '<span style="font-size:0.875rem;font-weight:600;color:white;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(inc.duck_id) + '</span>' +
                        sosBadge +
                        statusBadge +
                        retransBadge +
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
                        actionsHtml +
                        assignNotesHtml +
                        '</div>';
                });

                html += '</div>';

                if (hiddenCount > 0) {
                    html += '<p style="margin-top:0.75rem;font-size:0.75rem;color:#9ca3af;text-align:center;">+' + hiddenCount + ' more \u2014 see full dashboard for all active incidents</p>';
                }

                // Only repaint when content changed to avoid flicker
                if ($list.data("last-html") !== html) {
                    $list.html(html);
                    $list.data("last-html", html);

                    // Read-only (central aggregator) instance: dispatch
                    // controls are rendered above but must not be usable
                    // here (server also rejects via 403 — this is just UX).
                    if (DASHBOARD_READONLY) {
                        $list
                            .find('.inc-ack-btn, .inc-status-btn, .inc-notes-save, .inc-assign-select, .inc-notes-input')
                            .prop('disabled', true)
                            .addClass('opacity-40 pointer-events-none cursor-not-allowed');
                    }
                }
    }

    // One-click ACK re-send from incidents panel
    $(document).on('click', '.inc-ack-btn', function () {
        if (DASHBOARD_READONLY) return;
        var $btn   = $(this);
        var duckId = $btn.data('duck');
        var msgId  = $btn.data('msgid');
        $btn.prop('disabled', true).text('Sending…');
        $.ajax({
            type: 'POST',
            url:  '/dashboard/sos-ack',
            data: { duck_id: duckId, message_id: msgId },
            success: function () {
                $btn.text('ACK Sent ✓');
                setTimeout(pollIncidents, 600);
            },
            error: function () {
                $btn.prop('disabled', false).text('Failed');
            },
        });
    });

    // Incident lifecycle status update
    $(document).on('click', '.inc-status-btn', function () {
        if (DASHBOARD_READONLY) return;
        var $btn   = $(this);
        var msgId  = $btn.data('msgid');
        var status = $btn.data('status');
        $btn.prop('disabled', true).text('Updating…');
        $.ajax({
            type: 'PATCH',
            url:  '/dashboard/incidents/' + encodeURIComponent(msgId) + '/status',
            data: { status: status },
            success: function () { pollIncidents(); },
            error:   function () { $btn.prop('disabled', false).text('Failed'); },
        });
    });

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

    // Assign an incident to a responder
    $(document).on('change', '.inc-assign-select', function () {
        if (DASHBOARD_READONLY) return;
        var $sel   = $(this);
        var msgId  = $sel.data('msgid');
        var userId = $sel.val();
        $sel.prop('disabled', true);
        $.ajax({
            type: 'PATCH',
            url:  '/dashboard/incidents/' + encodeURIComponent(msgId) + '/assign',
            data: { user_id: userId || null },
            success: function () { pollIncidents(); },
            error:   function () { $sel.prop('disabled', false); },
        });
    });

    // Save a note on an incident
    $(document).on('click', '.inc-notes-save', function () {
        if (DASHBOARD_READONLY) return;
        var $btn   = $(this);
        var msgId  = $btn.data('msgid');
        var $input = $btn.siblings('.inc-notes-input');
        var notes  = $input.val();
        var origText = $btn.text();
        $btn.prop('disabled', true).text('Saving…');
        $.ajax({
            type: 'PATCH',
            url:  '/dashboard/incidents/' + encodeURIComponent(msgId) + '/notes',
            data: { notes: notes },
            success: function () {
                $btn.prop('disabled', false).text('Saved ✓');
                setTimeout(function () { $btn.text(origText); }, 1500);
            },
            error: function () { $btn.prop('disabled', false).text('Failed'); },
        });
    });

    // Acknowledge every open incident in one action
    $(document).on('click', '#bulk-ack-btn', function () {
        if (DASHBOARD_READONLY) return;
        var $btn = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).text('Acknowledging…');
        $.ajax({
            type: 'POST',
            url:  '/dashboard/incidents/bulk-acknowledge',
            success: function () {
                $btn.prop('disabled', false).html(origHtml);
                pollIncidents();
            },
            error: function () {
                $btn.prop('disabled', false).html(origHtml);
            },
        });
    });

    // Combined keyword search box (filters already-cached data, no re-fetch)
    $(document).on('input', '#incidents-search', function () {
        renderIncidentsList();
    });

    if ($("#incidents-list").length) {
        loadResponders();
        pollIncidents();
        setInterval(pollIncidents, 30000);
    }

    // ── Incident response SLA stats ─────────────────────────────────────────────
    function formatDuration(seconds) {
        if (seconds === null || seconds === undefined) return '—';
        if (seconds < 60) return Math.round(seconds) + 's';
        var mins = seconds / 60;
        if (mins < 60) return Math.round(mins) + 'm';
        return (mins / 60).toFixed(1) + 'h';
    }

    function pollSlaStats() {
        $.ajax({
            url: '/dashboard/incidents/stats',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                $('#sla-avg-ack').text(formatDuration(data.avg_ack_seconds));
                $('#sla-avg-resolve').text(formatDuration(data.avg_resolve_seconds));
                $('#sla-open-resolved').text((data.open_incidents || 0) + ' / ' + (data.resolved_incidents || 0));
            },
            error: function () {}
        });
    }

    if ($("#sla-stats").length) {
        pollSlaStats();
        setInterval(pollSlaStats, 60000);
    }

    // ── Duck health widget ─────────────────────────────────────────────────────
    function renderDuckHealth(ducks) {
        var $grid = $('#duck-health-grid');
        if (!$grid.length) return;
        if (!ducks.length) {
            $grid.html('<p class="text-xs italic text-gray-500 dark:text-gray-400">No ducks seen yet.</p>');
            return;
        }
        var html = '';
        ducks.forEach(function (duck) {
            var dotColor = duck.status === 'online' ? '#22c55e' : duck.status === 'idle' ? '#eab308' : '#6b7280';
            var typeLabel = duck.duck_type === 1 ? 'PapaDuck' : duck.duck_type === 2 ? 'MamaDuck' : duck.duck_type === 0 ? 'Operator' : 'Duck';
            var battClass = duck.battery < 20 ? 'text-red-600 dark:text-red-400' : duck.battery < 50 ? 'text-amber-600 dark:text-amber-300' : 'text-green-600 dark:text-green-300';
            var battHtml = duck.battery !== null
                ? '<span class="text-[0.65rem] font-semibold ' + battClass + '">' + duck.battery + '%</span>'
                : '';
            html += '<div class="flex flex-col gap-1 rounded-lg px-2.5 py-2 bg-gray-100 dark:bg-white/[0.04] ring-1 ring-inset ring-gray-200 dark:ring-white/[0.08]">' +
                '<div class="flex items-center gap-1.5">' +
                '<span style="width:8px;height:8px;border-radius:50%;background:' + dotColor + ';flex-shrink:0;box-shadow:0 0 4px ' + dotColor + ';"></span>' +
                '<span class="text-sm font-semibold text-gray-900 dark:text-gray-200 overflow-hidden text-ellipsis whitespace-nowrap flex-1">' + escapeHtml(duck.duck_id) + '</span>' +
                battHtml +
                '</div>' +
                '<div class="flex gap-1">' +
                '<span class="text-[0.65rem] text-gray-600 dark:text-gray-500">' + typeLabel + '</span>' +
                '<span class="text-[0.65rem] text-gray-400 dark:text-gray-600">&middot;</span>' +
                '<span class="text-[0.65rem] text-gray-600 dark:text-gray-500">' + escapeHtml(duck.last_seen) + '</span>' +
                '</div>' +
                '</div>';
        });
        if ($grid.data('last') !== html) {
            $grid.html(html);
            $grid.data('last', html);
        }

        if (typeof window.updateTrendsDuckOptions === 'function') {
            window.updateTrendsDuckOptions(ducks);
        }
    }

    function pollDuckHealth() {
        $.ajax({
            url: '/dashboard/duck-health',
            method: 'GET',
            dataType: 'json',
            success: renderDuckHealth,
            error: function () {},
        });
    }

    if ($('#duck-health-grid').length) {
        pollDuckHealth();
        setInterval(pollDuckHealth, 30000);
    }

    // ── Mesh topology panel ───────────────────────────────────────────────────
    function renderTopology(items) {
        var $list = $('#topology-list');
        if (!$list.length) return;
        if (!items.length) {
            $list.html('<p class="text-xs italic text-gray-500 dark:text-gray-400">No relay paths recorded yet.</p>');
            return;
        }
        var html = '<div style="display:flex;flex-direction:column;gap:6px;">';
        items.forEach(function (item) {
            var hops = item.path.split(',').map(function (h) { return h.trim(); });
            var chain = hops.map(function (h, i) {
                var isOrigin = i === 0;
                var isDest   = i === hops.length - 1;
                var c = isOrigin ? 'text-amber-600 dark:text-amber-400 font-bold' : isDest ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-200';
                return '<span class="font-mono text-[0.72rem] rounded bg-gray-200 dark:bg-white/[0.07] px-1.5 py-px ' + c + '">' + escapeHtml(h) + '</span>';
            }).join('<span class="text-gray-400 dark:text-gray-500 text-[0.7rem] px-1">&rarr;</span>');
            html += '<div class="flex flex-wrap items-center gap-1.5 rounded-md px-2 py-1.5 bg-gray-100 dark:bg-white/[0.03] ring-1 ring-inset ring-gray-200 dark:ring-white/[0.06]">' +
                chain +
                '<span class="ml-auto text-[0.65rem] text-gray-500 dark:text-gray-400 whitespace-nowrap">' + escapeHtml(item.hops) + ' hop' + (item.hops !== 1 ? 's' : '') + ' &nbsp;·&nbsp; ' + escapeHtml(item.created_at) + '</span>' +
                '</div>';
        });
        html += '</div>';
        if ($list.data('last') !== html) {
            $list.html(html);
            $list.data('last', html);
        }
    }

    function pollTopology() {
        $.ajax({
            url: '/dashboard/topology',
            method: 'GET',
            dataType: 'json',
            success: renderTopology,
            error: function () {},
        });
    }

    if ($('#topology-list').length) {
        pollTopology();
        setInterval(pollTopology, 30000);
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
                ? '<span class="inline-flex items-center gap-0.5 text-xs text-blue-600 dark:text-blue-300 mt-0.5">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3">' +
                  '<path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>' +
                  "Received</span>"
                : '<span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Sent</span>';
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
                '<div class="rounded-md px-3 py-1.5 text-sm bg-gray-100 dark:bg-white/10 text-gray-800 dark:text-gray-300 break-all">' +
                msgText +
                urgencyRow(payload) +
                "</div>"
            );
        }

        var text = escapeHtml(msg.text || msg.payload || "(no content)");
        return (
            '<div class="max-w-full rounded-md px-3 py-1.5 text-sm bg-gray-100 dark:bg-white/10 text-gray-800 dark:text-gray-300 break-all">' +
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
                                    '<div class="flex items-start gap-2 rounded-md bg-red-100 dark:bg-red-900/50 px-3 py-2 ring-1 ring-inset ring-red-300 dark:ring-red-500/40">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-700 dark:text-red-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-xs font-semibold text-red-700 dark:text-red-400">SOS \u2014 Hardware Button Triggered</p>' +
                                    '<p class="text-xs text-red-700/80 dark:text-red-300/80">This SOS was sent because the physical SOS button on the device was pressed.</p>' +
                                    sosDeviceTelemetryHtml(cp) +
                                    "</div>" +
                                    "</div>";
                            } else if (cisSosMob) {
                                bodyHtml =
                                    '<div class="flex items-start gap-2 rounded-md bg-orange-100 dark:bg-orange-900/50 px-3 py-2 ring-1 ring-inset ring-orange-300 dark:ring-orange-500/40">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-orange-700 dark:text-orange-400"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-xs font-semibold text-orange-700 dark:text-orange-400">SOS \u2014 Mobile Phone Triggered</p>' +
                                    '<p class="text-xs text-orange-700/80 dark:text-orange-300/80">This SOS was sent from the user\'s mobile phone application and should include GPS coordinates.</p>' +
                                    sosDeviceTelemetryHtml(cp) +
                                    "</div>" +
                                    "</div>";
                            } else if (cisRogerDev) {
                                bodyHtml =
                                    '<div class="flex items-start gap-2 rounded-md bg-green-100 dark:bg-green-900/50 px-3 py-2 ring-2 ring-inset ring-green-400 dark:ring-green-500/60">' +
                                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-green-700 dark:text-green-400"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" /></svg>' +
                                    '<div><p class="text-sm font-bold text-green-800 dark:text-green-300 uppercase tracking-wide">Roger \u2014 Device Confirmed</p>' +
                                    '<p class="text-xs text-green-700/80 dark:text-green-400/80">The person holding the device triple-clicked the button to confirm they have received your message.</p></div>' +
                                    "</div>";
                            } else {
                                var displayText =
                                    cardMsg.text || cardMsg.payload || "";
                                bodyHtml =
                                    '<p class="text-sm text-gray-500 dark:text-gray-400 break-words">' +
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
                            "critical-card flex flex-col divide-y divide-red-300 dark:divide-red-500/30 overflow-hidden rounded-lg bg-red-100 dark:bg-red-950/40 outline outline-2 -outline-offset-2 outline-red-500",
                        );
                        $header.attr(
                            "class",
                            "px-4 py-4 sm:px-6 flex flex-col gap-2 bg-red-200 dark:bg-red-900/50",
                        );
                        $duckId.attr(
                            "class",
                            "text-sm font-bold text-red-700 dark:text-red-300 tracking-wide",
                        );
                    } else {
                        $card.attr(
                            "class",
                            "flex flex-col divide-y divide-gray-200 dark:divide-white/10 overflow-hidden rounded-lg bg-white dark:bg-gray-800/50 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10",
                        );
                        $header.attr(
                            "class",
                            "px-4 py-4 sm:px-6 flex flex-col gap-2",
                        );
                        $duckId.attr(
                            "class",
                            "text-sm font-semibold text-gray-900 dark:text-white",
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

        // Incident badges: keep status pills on duck cards in sync with live incident state
        function pollIncidentBadges() {
            $.ajax({
                url: '/dashboard/incidents',
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    // Build a map of duck_id → most urgent unresolved incident status
                    var byDuck = {};
                    $.each(resp.data || [], function (i, inc) {
                        var s = inc.incident_status || 'open';
                        if (s === 'resolved') return; // skip closed
                        // Priority: responding > acknowledged > open
                        var pri = { open: 0, acknowledged: 1, responding: 2 };
                        if (!byDuck[inc.duck_id] || pri[s] > pri[byDuck[inc.duck_id]]) {
                            byDuck[inc.duck_id] = s;
                        }
                    });

                    $('[data-incident-badge-duck]').each(function () {
                        var duckId = $(this).data('incident-badge-duck');
                        var status = byDuck[duckId];
                        if (!status) {
                            var duckId2 = $(this).data('incident-badge-duck');
                            // `hidden` alone can lose the CSS cascade to the
                            // permanent `inline-flex` utility on this element
                            // (equal specificity, source-order dependent), so
                            // use the `!hidden` (important) variant and also
                            // strip any leftover color classes from the last
                            // visible state.
                            $(this)
                                .removeClass('bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 ring-red-300 dark:ring-red-500/40 bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 ring-amber-300 dark:ring-amber-500/40 bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 ring-blue-300 dark:ring-blue-500/40')
                                .addClass('!hidden')
                                .text('');
                            var $card2 = $('[data-duck-id="' + duckId2 + '"]');
                            if ($card2.length) $card2.attr('data-incident-status', '');
                            return;
                        }
                        var colorMap = {
                            open:         'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 ring-red-300 dark:ring-red-500/40',
                            acknowledged: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 ring-amber-300 dark:ring-amber-500/40',
                            responding:   'bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 ring-blue-300 dark:ring-blue-500/40',
                        };
                        var labelMap = { open: 'OPEN', acknowledged: "ACK'D", responding: 'RESP' };
                        $(this)
                            .removeClass('hidden !hidden bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 ring-red-300 dark:ring-red-500/40 bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 ring-amber-300 dark:ring-amber-500/40 bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 ring-blue-300 dark:ring-blue-500/40')
                            .addClass(colorMap[status] || colorMap['open'])
                            .text(labelMap[status] || 'OPEN');

                        // Keep data-incident-status on the card in sync for the filter
                        var $card = $('[data-duck-id="' + duckId + '"]');
                        if ($card.length) $card.attr('data-incident-status', status);
                    });
                },
                error: function () { /* silently ignore */ }
            });
        }

        pollIncidentBadges();
        setInterval(pollIncidentBadges, 10000);

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
