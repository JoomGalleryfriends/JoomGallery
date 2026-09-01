import { h, Fragment } from 'preact';
import prettierBytes from '@transloadit/prettier-bytes';
import truncateString from '@uppy/utils/lib/truncateString';
import MetaErrorMessage from '@uppy/dashboard/lib/components/FileItem/MetaErrorMessage.js';

const renderFileName = props => {
  const {
    author,
    name
  } = props.file.meta;

  function getMaxNameLength() {
    if (props.isSingleFile && props.containerHeight >= 350) {
      return 90;
    }

    if (props.containerWidth <= 352) {
      return 35;
    }

    if (props.containerWidth <= 576) {
      return 60;
    } // When `author` is present, we want to make sure
    // the file name fits on one line so we can place
    // the author on the second line.


    return author ? 20 : 30;
  }

  return h("div", {
    className: "uppy-Dashboard-Item-name",
    title: name
  }, truncateString(name, getMaxNameLength()));
};

const renderAuthor = props => {
  const {
    author
  } = props.file.meta;
  const {
    providerName
  } = props.file.remote;
  const dot = `\u00B7`;

  if (!author) {
    return null;
  }

  return h("div", {
    className: "uppy-Dashboard-Item-author"
  }, h("a", {
    href: `${author.url}?utm_source=Companion&utm_medium=referral`,
    target: "_blank",
    rel: "noopener noreferrer"
  }, truncateString(author.name, 13)), providerName ? h(Fragment, null, ` ${dot} `, providerName, ` ${dot} `) : null);
};

const renderFileSize = props => props.file.size && h("div", {
  className: "uppy-Dashboard-Item-statusSize"
}, prettierBytes(props.file.size));

const renderDebugBtn = props => props.file.debugBtn && h("div", {
  className: "uppy-Dashboard-Item-debug"
}, h("button", {
    class: "btn btn-"+props.file.debugBtn.type+" btn-sm "+props.file.debugBtn.style,
    type: "button",
    'data-bs-toggle': "modal",
    'data-bs-target': "#modal"+props.file.debugBtn.file_id,
  }, props.file.debugBtn.txt));

const renderState = props => props.file.statetxt && h("div", {
  className: "uppy-Dashboard-Item-state"
}, props.file.statetxt);

const ReSelectButton = props => props.file.isGhost && h("span", null, ' \u2022 ', h("button", {
  className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-reSelect",
  type: "button",
  onClick: props.toggleAddFilesPanel
}, props.i18n('reSelect')));

const ErrorButton = _ref => {
  let {
    file,
    onClick
  } = _ref;

  if (file.error) {
    return h("button", {
      className: "uppy-u-reset uppy-c-btn uppy-Dashboard-Item-errorDetails",
      "aria-label": file.error,
      "data-microtip-position": "bottom",
      "data-microtip-size": "medium",
      onClick: onClick,
      type: "button"
    }, "?");
  }

  return null;
};

export default function FileInfo(props) {
  const { file } = props;
  
  return h("div", {
    className: "uppy-Dashboard-Item-fileInfo",
    "data-uppy-file-source": file.source
  }, h("div", {
    className: "uppy-Dashboard-Item-fileName"
  }, renderFileName(props), h(ErrorButton, {
    file: props.file // eslint-disable-next-line no-alert
    ,
    onClick: () => alert(props.file.error) // TODO: move to a custom alert implementation

  })), h("div", {
    className: "uppy-Dashboard-Item-status"
  }, renderAuthor(props), renderFileSize(props), renderState(props), renderDebugBtn(props), ReSelectButton(props)), h(MetaErrorMessage, {
    file: props.file,
    i18n: props.i18n,
    toggleFileCard: props.toggleFileCard,
    metaFields: props.metaFields
  }));
}
