$.extend(UIOWA_AdminDash, {
  columnLabelMap: ["status", "purpose", "purpose_other"],

  codeTypeLabelMap: {
    2: [
      "Practice / Just for fun",
      "Other",
      "Research",
      "Quality Improvement",
      "Operational Support",
    ],
    3: [
      "Basic or Bench Research",
      "Clinical research study or trial",
      "Translational Research 1",
      "Translational Research 2",
      "Behavioral or psychosocial research study",
      "Epidemiology",
      "Repository",
      "Other",
    ],
    1: ["Development", "Production", "Analysis"],
  },

  sanitizeCellData: function (cellData) {
    // TODO https://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
    return cellData.replaceAll("<", "&lt;").replaceAll(">", "&gt;");
  },

  finalData: {},

  initDatatable: function () {
    let self = this;

    let tempFormatting = self.loadedReport.meta.column_formatting;

    if (tempFormatting !== undefined) {
      const columnConfigArray = Object.entries(
        self.loadedReport.meta.column_formatting
      );

      let researchPurposeIndex = "";

      for (let i = 0; i < columnConfigArray.length; i++) {
        const column = columnConfigArray[i];
        const columnName = column[0];
        const columnConfig = column[1];

        // purposeOtherConfig =

        let newColumns = {};

        if (columnConfig.code_type === "4") {
          researchPurposeIndex = columnName;

          for (let j = 0; j < self.codeTypeLabelMap["3"].length; j++) {
            const tempColumnConfig = {
              ...columnConfig,
              ["column_name"]: self.codeTypeLabelMap["3"][j],
              // ["link_source_column"]: "purpose_other",
              ["code_type"]: "",
              ["dashboard_display_header"]: self.codeTypeLabelMap["3"][j],
            };

            newColumns = {
              ...newColumns,
              [JSON.stringify(self.codeTypeLabelMap["3"][j])]: tempColumnConfig,
            };
          }

          tempFormatting = {
            ...tempFormatting,
            ...newColumns,
          };

          self.loadedReport.meta.column_formatting[columnName] = newColumns;
          delete self.loadedReport.meta.column_formatting[researchPurposeIndex];
        }
      }
    }

    // report edit shortcut (admins only)
    $(".edit-report").click(function () {
      let loadedReportMeta = self.loadedReport.meta;
      let url =
        self.urlLookup.redcapBase +
        "/DataEntry/record_home.php?pid=" +
        self.configPID +
        "&id=" +
        loadedReportMeta.config.report_id;

      if ("project_join_info" in loadedReportMeta) {
        url += "&arm=2";
      }

      window.open(url, "_blank");
    });

    let data = self.loadedReport.data;
    let columns = $.map(self.loadedReport.columns, function (column_name) {
      return {
        title: column_name,
        data: column_name,
        className: "",
        contentPadding: "mmm",
        createdCell: function (td, cellData, rowData, row, col) {
          $(td).css("text-align", "center");
        },
      };
    });

    if (self.loadedReport.meta.column_formatting) {
      // set column titles and renderers
      columns = $.map(self.loadedReport.columns, function (column_name) {
        let columnDetails =
          self.loadedReport.meta.column_formatting[column_name];
        let column = {
          //  TODO get proper column name
          //  This is the value that will display as the column header.
          title:
            columnDetails.dashboard_display_header !== ""
              ? columnDetails.dashboard_display_header
              : column_name,
          data: column_name,
          // className: "",
          className: columnDetails.dashboard_show_column === "0" ? "noVis" : "",
          contentPadding: "mmm",
          createdCell: function (td, cellData, rowData, row, col) {
            $(td).css("text-align", "center");
          },
        };

        // only apply column formatting for sql reports
        //  This code controls the TD formatting.
        if (self.loadedReport.meta.config.report_sql !== "") {
          $.fn.dataTable.render.adFormat = function (column_name) {
            return function (data, type, row) {
              return self.adFormat(column_name, data, type, row);
            };
          };

          column.render = $.fn.dataTable.render.adFormat(column_name);
        }

        return column;
      });

      // add column for child row collapse buttons (if at least one column needs it)
      // let hasChildRow = false;
      // $.each(self.loadedReport.meta.column_formatting, function (column_name, value) {
      //
      //     if (value.dashboard_show_column === '2') {
      //         hasChildRow = true;
      //     }
      // });
      // if (hasChildRow) {
      //     $('.report-table > thead > tr:first').prepend('<th></th>');
      //
      //     columns.unshift({
      //         className: 'details-control',
      //         orderable: false,
      //         data: null,
      //         defaultContent: '',
      //         render: function () {
      //             return '<i class="fa fa-plus-square" aria-hidden="true"></i>';
      //         },
      //         width:"15px"
      //     });
      // }
    }

    // init DataTable
    let table = $(".report-table").DataTable({
      data: data,
      scrollXInner: true,
      // scrollY: true,
      // stateSave: true, todo - saved sorting can be confusing
      colReorder: true,
      fixedHeader: {
        header: true,
        headerOffset: $("#redcap-home-navbar-collapse").height(),
      },
      columns: columns,
      order: [],
      initComplete: function () {
        let hasFilters = false;
        let $filterRow = $('<tr class="filter-row"></tr>');

        // add column filters
        this.api()
          .columns()
          .every(function () {
            let $filter = self.dtFilterInit(this);

            if ($filter) {
              $filterRow.append($filter);
              hasFilters = true;
            }
          });

        if (hasFilters) {
          $("thead").append($filterRow);
        }
      },
    });

    // generate export buttons

    self.dtExportInit(table);
    if (
      self.loadedReport.ready &&
      ((self.executiveView && self.executiveExport) || !self.executiveView)
    ) {
      $("#buttons").show(); //  TODO change this so buttons won't render at all instead of just being hidden
    }
    // } else {
    //     $("#buttons").hide();
    // }

    // show/hide columns

    if (self.loadedReport.ready) {
      new $.fn.dataTable.Buttons(table, {
        buttons: [
          {
            text: "Show/Hide Columns",
            extend: "colvis",
            columns: ":not(.noVis)",
          },
        ],
      })
        .container()
        .appendTo($("#visButtons"));

      // sync filter visibility with column
      table.on("column-visibility.dt", function (e, settings, column, state) {
        let $filterTd = $(".filter-row > td").eq(column);

        state ? $filterTd.show() : $filterTd.hide();
      });
    }

    // child row show/hide logic
    $(".report-table tbody").on("click", "td.details-control", function () {
      let tr = $(this).closest("tr");
      let row = table.row(tr);

      if (row.child.isShown()) {
        // This row is already open - close it
        row.child.hide();
        tr.removeClass("shown");
      } else {
        // Open this row
        row.child(self.formatChildRow(row.data())).show();
        tr.addClass("shown");
      }
    });
  },
  formatChildRow: function (row) {
    let self = this;
    let columnDetails = this.loadedReport.meta.column_formatting;
    let htmlRows = "";

    $.each(columnDetails, function (column_name, details) {
      let data = self.adFormat(column_name, row[column_name], row);

      if (details.dashboard_show_column === "2") {
        htmlRows = htmlRows.concat(
          "<tr>" +
            "<td>" +
            (details.dashboard_display_header !== ""
              ? details.dashboard_display_header
              : column_name) +
            "</td>" +
            "<td>" +
            data +
            "</td>" +
            "</tr>"
        );
      }
    });

    // `d` is the original data object for the row
    return (
      '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
      htmlRows +
      "</table>"
    );
  },
  splitData: function (data, column_name) {
    let separator =
      this.loadedReport.meta.column_formatting[column_name]
        .group_concat_separator;

    return separator !== "" ? data.split(separator) : [data];
  },
  dtFilterInit: function (column) {
    let self = this;
    let $filterTd = $('<td data-column-index="' + column.index() + '"></td>');
    let column_name = self.loadedReport.columns[column.index()];

    let columnDetails =
      self.loadedReport.meta.column_formatting !== undefined
        ? self.loadedReport.meta.column_formatting[column_name]
        : undefined;

    // todo
    if (column_name === undefined) {
      return;
    }

    // hide column
    if (
      columnDetails !== undefined &&
      columnDetails.dashboard_show_column === "0"
    ) {
      column.visible(false);
      return;
    }

    // add dropdown filter
    if (
      columnDetails !== undefined &&
      columnDetails.dashboard_show_filter === "2"
    ) {
      $filterTd.append(
        '<select style="width: 100%"><option value=""></option></select>'
      );
      let $select = $filterTd.find("select").on("change", function () {
        let val = $.fn.dataTable.util.escapeRegex($(this).val());

        column
          // .search( val ? '^'+val+'$' : '', true, false || val ).draw()
          .search(val, true, false)
          .draw();
      });

      const columnData = column.data();
      let uniqueNames = [];
      $.each(columnData, function (i, el) {
        if ($.inArray(el, uniqueNames) === -1) uniqueNames.push(el);
      });

      let multiPurpose = [];
      $.each(uniqueNames, function (idx, value) {
        // todo filtering for null values
        if (value !== null) {
          if (value.length >= 2 && !multiPurpose.includes(value)) {
            multiPurpose = [...multiPurpose, value];
          }

          let labels = [];

          if (columnDetails !== undefined && columnDetails.code_type !== "") {
            labels = self.codeTypeLabelMap[columnDetails.code_type];
          } else if (
            columnDetails !== undefined &&
            columnDetails.code_type === "" &&
            columnDetails.column_name == "purpose_other"
          ) {
            labels = self.codeTypeLabelMap[3];
          }

          if (
            columnDetails !== undefined &&
            columnDetails.code_type !== "" &&
            self.columnLabelMap.includes(columnDetails.column_name)
          ) {
            if (idx === 0) {
              $.each(labels, function (idx2, option) {
                $select.append(
                  '<option value="' + option + '">' + option + "</option>"
                );
              });
            }
          } else if (
            columnDetails !== undefined &&
            columnDetails.code_type === "" &&
            self.columnLabelMap.includes(columnDetails.column_name)
          ) {
            if (idx === 0) {
              $.each(labels, function (idx2, option) {
                $select.append(
                  '<option value="' + idx2 + '">' + idx2 + "</option>"
                );
              });
            }
          } else {
            $select.append(
              '<option value="' + value + '">' + value + "</option>"
            );
          }
        } else {
          $select.append('<option value="null">[null]</option>');
        }
      });
    }
    // add free text filter
    else if (
      columnDetails === undefined ||
      columnDetails.dashboard_show_filter === "1"
    ) {
      $filterTd.append('<input style="width: 100%"/>');

      $("input", $filterTd).on("keyup change clear", function () {
        if (column.search() !== this.value) {
          // todo split grouped data and filter items
          // let groupData = column.data().split()

          column.search(this.value).draw();
        }
      });
    }

    return $filterTd;
  },
  dtExportInit: function (table) {
    let buttonCommon = {
      title: this.loadedReport.meta.config.report_title,
      exportOptions: {
        orthogonal: "export",
        // format: {
        //     body: function ( data, row, column, node ) {
        //         // Strip $ from salary column to make it numeric
        //         return column === 5 ?
        //             data.replace( /[$,]/g, '' ) :
        //             data;
        //     }
        // }
      },
    };
    if (
      this.loadedReport.ready &&
      ((self.executiveView && self.executiveExport) || !self.executiveView)
    ) {
      // export buttons
      new $.fn.dataTable.Buttons(table, {
        buttons: [
          $.extend(true, {}, buttonCommon, {
            extend: "copyHtml5",
          }),
          $.extend(true, {}, buttonCommon, {
            extend: "csvHtml5",
          }),
          // $.extend( true, {}, buttonCommon, {
          //     extend: 'pdfHtml5'
          // } ),
          $.extend(
            true,
            {
              text: "JSON",
              action: function (e, dt, button, config) {
                let data = dt.buttons.exportData();

                $.fn.dataTable.fileSave(
                  new Blob([JSON.stringify(data)]),
                  buttonCommon.title + ".json"
                );
              },
            },
            buttonCommon
          ),
        ],
      })
        .container()
        .appendTo($("#buttons"));
    }
  },
  adFormat: function (column_name, data, type, row) {
    if (data === null) {
      return type === "display"
        ? '<span class="text-muted">null</span>'
        : "null";
    }

    let self = this;

    let columnDetails = self.loadedReport.meta.column_formatting[column_name];

    let sourceColumn =
      columnDetails.link_source_column !== ""
        ? columnDetails.link_source_column
        : column_name;

    data = self.splitData(data, sourceColumn);
    let sourceData = data;
    let formattedSeparator = type === "export" ? ";" : "<br />";

    if (sourceColumn !== column_name) {
      sourceData = self.splitData(row[sourceColumn], sourceColumn);
    }

    // for each item (in case data is grouped)
    data = $.map(data, function (item, index) {
      if (item === null) {
        return type === "display"
          ? '<span class="text-muted">null</span>'
          : "null";
      }

      //todo
      // fix for "Archived" projects
      // if (value === 3) {
      //     value = 2;
      // }

      let formattedVal = item;
      let rawUrl = "";
      let iconsHtml = "";

      // Replace coded value with label
      if (columnDetails.code_type !== "") {
        try {
          // formattedVal = "hi";
          // if export, check if labels are preferred
          if (type === "export" && columnDetails.export_codes === "0") {
            formattedVal = item;
          } else {
            if (columnDetails.code_type === "1") {
              formattedVal = self.adFormat_code(item, columnDetails.code_type);
            }
            if (columnDetails.code_type === "2") {
              formattedVal = self.adFormat_code(item, columnDetails.code_type);
            } else if (
              columnDetails.code_type === "3" ||
              columnDetails.code_type === "4"
            ) {
              const arrayOfFormattedVals = item.split(",");
              let codesAsLabels = "";
              $.each(arrayOfFormattedVals, function (idx, value) {
                const index = self.codeTypeLabelMap[3].indexOf(value);
                if (idx === arrayOfFormattedVals.length - 1) {
                  codesAsLabels += self.codeTypeLabelMap[3][value];
                } else {
                  codesAsLabels += self.codeTypeLabelMap[3][value] + ", ";
                }
              });

              formattedVal = codesAsLabels;
            } else {
              formattedVal = self.adFormat_code(item, columnDetails.code_type);
            }
          }

          if (type === "filter") {
            return formattedVal; //todo broken
          }
        } catch (e) {
          console.groupCollapsed(
            "Failed to replace codes with labels for " + column_name
          );

          console.groupEnd();
        }
      }

      // generate url for linking
      if (columnDetails.link_type !== "" && !self.executiveView) {
        try {
          // formattedVal = "hi";

          rawUrl = self.adFormat_url(
            item,
            sourceData[index],
            columnDetails.link_type,
            columnDetails.specify_custom_link
          );

          if (type === "export") {
            if (columnDetails.export_urls === "1") {
              formattedVal = self.sanitizeCellData(rawUrl);
            } else {
              formattedVal = self.sanitizeCellData(item);
            }
          } else if (type === "filter") {
            formattedVal = self.sanitizeCellData(item);
          } else {
            formattedVal = `<a href="${rawUrl}" target="_blank">${self.sanitizeCellData(
              formattedVal
            )}</a>`; //$.fn.dataTable.render.text()
          }
        } catch (e) {
          console.groupCollapsed(
            "Failed to generate url(s) for " + column_name
          );

          console.groupEnd();
        }
      }

      // prepend hint icons
      if (
        (columnDetails.hint_icons___1 === "1" ||
          columnDetails.hint_icons___2 === "1") &&
        type === "display"
      ) {
        try {
          let columnReference = {
            withTags: self.loadedReport.columns,
            tagless: $.map(self.loadedReport.columns, function (value) {
              return value.split("#")[0];
            }),
          };

          if (item) {
            iconsHtml = self.adFormat_icons(
              item,
              index,
              row,
              columnReference,
              columnDetails
            );
          }
        } catch (e) {
          console.groupCollapsed(
            "Failed to process hint icon(s) for " + column_name
          );

          console.groupEnd();
        }
      }

      return iconsHtml + formattedVal;
    });
    data = data.join(formattedSeparator);

    return data;
  },
  adFormat_url: function (value, sourceValue, linkIndex, customUrl) {
    let url = "";

    // set custom url
    if (linkIndex === "99") {
      url = customUrl.replace("{value}", sourceValue);
    }
    // set mailto
    else if (linkIndex === "9") {
      url = "mailto:" + sourceValue;
    }
    // set redcap url
    else {
      try {
        url =
          this.urlLookup.redcapBase +
          this.formattingReference.links[linkIndex - 1].trim() +
          sourceValue;
      } catch (error) {
        // invalid link index
        console.error(error);
        return value;
      }
    }

    return url;
  },
  adFormat_code: function (value, codeIndex) {
    if (codeIndex === "1") {
      // Project Status
      return this.formattingReference.status[value];
    } else if (codeIndex === "2") {
      // Project Purpose
      return this.formattingReference.purpose[value];
    } else if (codeIndex === "3") {
      // Research/Other Purpose multiple

      let valueArray = value.split(",");

      if (Array.isArray(valueArray) && !valueArray.some(isNaN)) {
        valueArray = $.map(value, function (code) {
          return self.formattingReference.purpose_other[code];
        });

        value = valueArray.join(", ");
      }

      return value;
    }
  },
  adFormat_icons: function (value, index, row, columnReference, columnDetails) {
    let returnHtml = "";

    // suspended users
    if (
      columnDetails.hint_icons___1 === "1" &&
      columnReference.tagless.includes("user_suspended_time")
    ) {
      let suspendedColumnName =
        columnReference.withTags[
          columnReference.tagless.indexOf("user_suspended_time")
        ];
      let suspendedValue =
        row[suspendedColumnName] !== null
          ? this.splitData(row[suspendedColumnName], suspendedColumnName)[index]
          : null;
      if (suspendedValue !== null && suspendedValue.length > 8) {
        returnHtml += `<span class="user-detail" title="User suspended" data-toggle="tooltip" data-placement="left">
                    <i class="fas fa-ban fa-fw" style="color: red;"></i>
                </span>`;
      }
    }
    // project status
    if (columnDetails.hint_icons___2 === "1") {
      let hintIcon = {};

      if (columnReference.tagless.includes("status")) {
        let iconLookup = [
          {
            class: "",
            tooltip: "Development",
            icon: "wrench",
            color: "#444",
          },
          {
            class: "",
            tooltip: "Production",
            icon: "check-square",
            color: "#00A000",
          },
          {
            class: "",
            tooltip: "Analysis/Cleanup",
            icon: "minus-circle",
            color: "#A00000",
          },
        ];

        let statusColumnName =
          columnReference.withTags[columnReference.tagless.indexOf("status")];
        let statusValue = this.splitData(
          row[statusColumnName],
          statusColumnName
        )[index];

        hintIcon = iconLookup[statusValue];
      }
      if (columnReference.tagless.includes("completed_time")) {
        let completedColumnName =
          columnReference.withTags[
            columnReference.tagless.indexOf("completed_time")
          ];
        let completedValue = this.splitData(
          row[completedColumnName],
          completedColumnName
        )[index];

        if (completedValue) {
          hintIcon = {
            class: "",
            tooltip: "Completed",
            icon: "archive",
            color: "#C00000",
          };
        }
      }
      if (columnReference.tagless.includes("date_deleted")) {
        let deletedColumnName =
          columnReference.withTags[
            columnReference.tagless.indexOf("date_deleted")
          ];
        let deletedValue = this.splitData(
          row[deletedColumnName],
          deletedColumnName
        )[index];

        if (deletedValue) {
          hintIcon = {
            class: "",
            tooltip: "Deleted",
            icon: "trash",
            color: "#A00000",
          };
        }
      }

      returnHtml += `<span class="${hintIcon.class}" title="${hintIcon.tooltip}" data-toggle="tooltip" data-placement="left">
                    <i class="fas fa-${hintIcon.icon} fa-fw" style="color: ${hintIcon.color};"></i>
                </span>`;
    }

    return returnHtml;
  },
  csvTo2dArray: function (parseMe) {
    const splitFinder = /,|\r?\n|"(\\"|[^"])*?"/g;
    let currentRow = [];
    const rowsOut = [currentRow];
    let lastIndex = (splitFinder.lastIndex = 0);

    // add text from lastIndex to before a found newline or comma
    const pushCell = (endIndex) => {
      endIndex = endIndex || parseMe.length;
      const addMe = parseMe.substring(lastIndex, endIndex);
      // remove quotes around the item
      currentRow.push(addMe.replace(/^"|"$/g, ""));
      lastIndex = splitFinder.lastIndex;
    };

    let regexResp;
    // for each regexp match (either comma, newline, or quoted item)
    while ((regexResp = splitFinder.exec(parseMe))) {
      const split = regexResp[0];

      // if it's not a quote capture, add an item to the current row
      // (quote captures will be pushed by the newline or comma following)
      if (split.startsWith(`"`) === false) {
        const splitStartIndex = splitFinder.lastIndex - split.length;
        pushCell(splitStartIndex);

        // then start a new row if newline
        const isNewLine = /^\r?\n$/.test(split);
        if (isNewLine) {
          rowsOut.push((currentRow = []));
        }
      }
    }
    // make sure to add the trailing text (no commas or newlines after)
    pushCell();
    return rowsOut;
  },
  generateMultiColumnResearchPurpose: function () {
    const columnConfigArray = Object.entries(
      UIOWA_AdminDash.loadedReport.meta.column_formatting
    );

    // let researchPurposeIndex = "";

    let finalColumns = [];

    for (let i = 0; i < columnConfigArray.length; i++) {
      const column = columnConfigArray[i];
      const columnName = column[0];
      const columnConfig = column[1];

      let newColumns = UIOWA_AdminDash.loadedReport.meta.column_formatting;

      if (columnConfig.code_type === "4") {
        researchPurposeIndex = columnName;

        for (let j = 0; j < UIOWA_AdminDash.codeTypeLabelMap["3"].length; j++) {
          const tempColConfig = {
            ...columnConfig,
            ["column_name"]: UIOWA_AdminDash.codeTypeLabelMap["3"][j],
            // ["link_source_column"]: "purpose_other",
            ["code_type"]: "",
            ["dashboard_display_header"]:
              UIOWA_AdminDash.codeTypeLabelMap["3"][j],
          };

          newColumns = {
            ...newColumns,
            [JSON.stringify(UIOWA_AdminDash.codeTypeLabelMap["3"][j])]:
              tempColConfig,
          };

          finalColumns = [
            ...finalColumns,
            JSON.stringify(UIOWA_AdminDash.codeTypeLabelMap["3"][j]),
          ];
        }

        tempFormatting = {
          ...tempFormatting,
          ...newColumns,
        };

        UIOWA_AdminDash.loadedReport.meta.column_formatting = tempFormatting;
      } else {
        finalColumns = [...finalColumns, columnName];
      }
    }
    return finalColumns;
  },

  generateMultiColumnResearchPurposeColumns: function (idx, columns) {
    const removeIndex = columns.indexOf(idx);
    purposeOtherIndex = removeIndex;
    purposeOtherName = idx;

    const newArray = columns.toSpliced(removeIndex, 1);

    newArray.splice(removeIndex, 0, ...UIOWA_AdminDash.codeTypeLabelMap[3]);
    return newArray;
  },
  generateMultiColumnResearchPurposeData: function (newJson, columnFormatting) {
    tempFormatting = UIOWA_AdminDash.loadedReport.meta.column_formatting;
    for (let i7 = 0; i7 < newJson.length; i7++) {
      let row = newJson[i7];

      let newData = {};
      const rowProps = Object.entries(columnFormatting);

      for (let i8 = 0; i8 < rowProps.length; i8++) {
        // const propName = rowProps[i8][0];
        const propConfig = rowProps[i8][1];

        if (propConfig.code_type === "4") {
          const purposeOtherValues = row.purpose_other.split(",");

          for (
            let idx10 = 0;
            idx10 < UIOWA_AdminDash.codeTypeLabelMap["3"].length;
            idx10++
          ) {
            newData = {
              ...newData,
              [JSON.stringify(UIOWA_AdminDash.codeTypeLabelMap["3"][idx10])]:
                purposeOtherValues.includes(JSON.stringify(idx10))
                  ? "TRUE"
                  : "FALSE",
            };
          }

          row = { ...row, ...newData };

          delete row["purpose_other"];
          newJson[i7] = row;
        }
      }
    }
    return newJson;
  },
});

$(document).ready(function () {
  let self = UIOWA_AdminDash;

  // initialize Vue.js
  new Vue({
    el: "#adminDashApp",
    data: self,
    updated: function () {
      this.$nextTick(function () {
        $("#adminDashApp").show();
        self.initDatatable();
      });
    },
    methods: {
      isActiveReport: function (id) {
        let loadedId = self.loadedReport.meta.config.report_id;

        return id === loadedId ? "active" : "";
      },
      getReportIcon: function (icon) {
        return icon !== "" ? "fas fa-" + icon : "fas fa-file";
      },
      getDisplayHeader: function (column_name) {
        if (typeof self.loadedReport.meta.column_formatting !== "undefined") {
          let columnDetails =
            self.loadedReport.meta.column_formatting[column_name];
          if (columnDetails !== undefined) {
            column_name =
              columnDetails.dashboard_display_header !== ""
                ? columnDetails.dashboard_display_header
                : column_name;
          } else {
            column_name = column_name;
          }
        }

        return column_name;
      },
      getReports: function (reportLookup, inFolders) {
        let formattedLookup = inFolders ? {} : [];

        $.each(reportLookup, function (index, report) {
          if (report.report_title === "") {
            report.report_title = "Untitled Report";
          }

          if (inFolders) {
            if (report.folder_name !== "") {
              if (!(report.folder_name in formattedLookup)) {
                formattedLookup[report.folder_name] = [];
              }

              formattedLookup[report.folder_name].push(report);
            }
          } else if (report.folder_name === "") {
            formattedLookup.push(report);
          }
        });

        return formattedLookup;
      },
      getTabColor: function (reportMeta, forFont = false) {
        let tab_color = reportMeta.tab_color;
        let tab_color_custom = reportMeta.tab_color_custom;

        if (tab_color === "99") {
          // use custom color
          tab_color = tab_color_custom.replace("#", "");
        } else if (tab_color === "") {
          // no color defined, skip
          return;
        }

        if (forFont) {
          // break hex code apart
          let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(
            tab_color
          );
          let color = {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
          };

          // return black/white for light/dark color
          return color.r * 0.299 + color.g * 0.587 + color.b * 0.114 > 186
            ? "#000000"
            : "#ffffff";
        } else {
          tab_color = "#" + tab_color;
        }

        return tab_color;
      },
    },
  });

  let reportId = self.loadedReport.meta.config.report_id;
  const getQueryData = new URLSearchParams();
  // data.append('query', sql)
  getQueryData.append("redcap_csrf_token", UIOWA_AdminDash.redcap_csrf_token);
  getQueryData.append("id", reportId);
  const requestType = self.loadedReport.meta.project_join_info
    ? "joinProjectData"
    : "getQuery";

  if (
    (UIOWA_AdminDash.showAdminControls == 1 && requestType === "getQuery") ||
    requestType === "joinProjectData"
  ) {
    console.log("show admin control");
    getQueryData.append("adMethod", requestType);

    if (requestType === "getQuery") {
      console.log("get query");
      fetch(UIOWA_AdminDash.urlLookup.post, {
        method: "POST",
        body: getQueryData,
      })
        .then((response) => response.text())
        .then((data) => {
          if (
            !data.toLowerCase().startsWith("error") &&
            data.toLowerCase().startsWith("select") &&
            !data.toLowerCase().startsWith("{&quot")
          ) {
            const dbQueryToolUrl =
              self.redcap_version_url +
              "ControlCenter/database_query_tool.php?export=1";
            const getData = new URLSearchParams();
            getData.append(
              "redcap_csrf_token",
              UIOWA_AdminDash.redcap_csrf_token
            );
            getData.append("query", data);

            fetch(dbQueryToolUrl, {
              method: "POST",
              body: getData,
            })
              .then((response) => response.text())
              .then((data) => {
                if (data.startsWith('<p class="red">')) {
                  self.loadedReport.error = "Database Query Tool disabled.";
                  self.loadedReport.ready = false;

                  $("#reportLoading").html("");
                } else {
                  const dataArrayized = self.csvTo2dArray(data);

                  let newJson = [];
                  const headers = dataArrayized[0];

                  for (let i = 1; i < dataArrayized.length; i++) {
                    let rowObject = {};

                    for (let i2 = 0; i2 < dataArrayized[i].length; i2++) {
                      rowObject[headers[i2]] = dataArrayized[i][i2];
                    }
                    newJson = [...newJson, rowObject];
                  }

                  if (newJson.length >= 1) {
                    let columns = [];

                    let columnFormatting =
                      self.loadedReport.meta.column_formatting;

                    let purposeOtherName = "";
                    let hasMultiColumnResearchPurpose = false;
                    if (columnFormatting) {
                      columns = Object.keys(columnFormatting);

                      const parseColumnFormatting =
                        Object.entries(columnFormatting);

                      for (const [idx, column] of parseColumnFormatting) {
                        const codeType = column.code_type;
                        if (codeType === "4") {
                          hasMultiColumnResearchPurpose = true;

                          columns =
                            self.generateMultiColumnResearchPurposeColumns(
                              idx,
                              columns
                            );
                        }
                      }

                      columns = $.map(
                        columnFormatting,
                        function (columnMeta, column_name) {
                          if (
                            columnMeta.code_type === "4" &&
                            column_name === purposeOtherName
                          ) {
                            return columnMeta.dashboard_show_column === "0"
                              ? null
                              : [...self.codeTypeLabelMap[3]];
                          } else {
                            return columnMeta.dashboard_show_column === "0"
                              ? null
                              : column_name;
                          }
                        }
                      );
                    }

                    if (hasMultiColumnResearchPurpose) {
                      newJson = self.generateMultiColumnResearchPurposeData(
                        newJson,
                        columnFormatting
                      );
                    }

                    columns = self.generateMultiColumnResearchPurpose();

                    $.extend(self.loadedReport, {
                      columns: columns,
                      data: newJson,
                      ready: true,
                    });
                  } else {
                    self.loadedReport.error = "Error.  Zero rows returned";
                    self.loadedReport.ready = false;
                    $("#reportLoading").html("");
                  }
                }
              });
          } else {
            self.loadedReport.error = "Error.  Zero rows returned";
            self.loadedReport.ready = false;
            $("#reportLoading").html("");
          }
        });
    } else if (requestType === "joinProjectData") {
      fetch(UIOWA_AdminDash.urlLookup.post, {
        method: "POST",
        body: getQueryData,
      })
        .then((response) => response.text())
        .then((data) => {
          if (data !== "" && !data.toLowerCase().startsWith("error")) {
            data = data.replaceAll("&quot;", '"');
            data = JSON.parse(data);

            let columns = [];

            columns = Object.keys(data[0]);

            $.extend(self.loadedReport, {
              columns: columns,
              data: data,
              ready: true,
            });
          } else {
            self.loadedReport.error = "Error.  Zero rows returned";
            self.loadedReport.ready = false;
            $("#reportLoading").html("");
          }
        });
    }
  } else if (UIOWA_AdminDash.executiveView) {
    console.log("exec view");
    getQueryData.append("adMethod", "runExecutiveReport");
    fetch(UIOWA_AdminDash.urlLookup.post, {
      method: "POST",
      body: getQueryData,
    })
      .then((response) => response.text())
      .then((data) => {
        console.log(data);
        if (
          data !== "" &&
          !data.toLowerCase().startsWith("error") &&
          !data.toLowerCase().startsWith("{&quot")
        ) {
          let newJson = data.replaceAll("&quot;", '"');
          newJson = JSON.parse(newJson);

          let columns = [];

          let columnFormatting = self.loadedReport.meta.column_formatting;
          let purposeOtherName = "";
          let hasMultiColumnResearchPurpose = false;
          if (columnFormatting) {
            columns = Object.keys(columnFormatting);

            const parseColumnFormatting = Object.entries(columnFormatting);

            for (const [idx, column] of parseColumnFormatting) {
              const codeType = column.code_type;
              if (codeType === "4") {
                hasMultiColumnResearchPurpose = true;

                columns = self.generateMultiColumnResearchPurposeColumns(
                  idx,
                  columns
                );
              }
            }

            columns = $.map(
              columnFormatting,
              function (columnMeta, column_name) {
                if (
                  columnMeta.code_type === "4" &&
                  column_name === purposeOtherName
                ) {
                  return columnMeta.dashboard_show_column === "0"
                    ? null
                    : [...self.codeTypeLabelMap[3]];
                } else {
                  return columnMeta.dashboard_show_column === "0"
                    ? null
                    : column_name;
                }
              }
            );
          }

          if (hasMultiColumnResearchPurpose) {
            newJson = self.generateMultiColumnResearchPurposeData(
              newJson,
              columnFormatting
            );
            columns = self.generateMultiColumnResearchPurpose();
          }

          self.loadedReport.ready = true;
          $.extend(self.loadedReport, {
            columns: columns,
            data: newJson,
            ready: true,
          });
        } else {
          self.loadedReport.error = "Error.  Zero rows returned";
          self.loadedReport.ready = false;
          $("#reportLoading").html("");
        }
      });
  }
});
